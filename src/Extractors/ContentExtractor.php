<?php

namespace Goose\Extractors;

use Goose\Utils\Debug;
use Goose\DOM\DOMElement;

class ContentExtractor {
    private static $SPLITTER_CHARS = [
        '|', '-', '»', ':',
    ];

    private static $A_REL_TAG_SELECTOR = "a[rel='tag'], a[href*='/tag/']";

    private static $TOP_NODE_TAGS = 'p, td, pre';

    private $config;

    private $logPrefix = 'ContentExtractor: ';

    public function __construct($config) {
        $this->config = $config;
    }

    public function getTitle($article) {
        $nodes = $article->getDoc()->filter('title');

        if (!$nodes->length) return '';

        $titleText = $nodes->item(0)->textContent;

        foreach (self::$SPLITTER_CHARS as $char) {
            if (strpos($titleText, $char)) {
                $length = 0;

                $parts = explode($char, $titleText);

                foreach ($parts as $part) {
                    if (mb_strlen($part) > $length) {
                        $length = mb_strlen($part);
                        $titleText = $part;
                    }
                }

                if ($length > 0) {
                    break;
                }
            }
        }

        return $titleText;
    }

    private function getNodesByLowercasePropertyValue($doc, $tag, $property, $value) {
        return $doc->filterXPath("descendant-or-self::".$tag."[translate(@".$property.", 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='".$value."']");
    }

    private function getMetaContent($doc, $property, $value, $attr = 'content') {
        $nodes = $this->getNodesByLowercasePropertyValue($doc, 'meta', $property, $value);

        if (!$nodes->length) {
            return '';
        }

        $content = $nodes->item(0)->getAttribute($attr);
        $content = trim($content);

        return $content;
    }

    /**
     * if the article has meta language set in the source, use that
     */
    public function getMetaLanguage($article) {
        $lang = '';

        $el = $article->getDoc()->filter('html[lang]');

        if ($el->length) {
            $lang = $el->item(0)->getAttribute('lang');
        }

        if (empty($lang)) {
            $selectors = [
                'meta[http-equiv=content-language]',
                'meta[name=lang]',
            ];

            foreach ($selectors as $selector) {
                $el = $article->getDoc()->filter($selector);

                if ($el->length && method_exists($el, 'getAttribute')) {
                    $attr = $el->getAttribute('content');
                    break;
                }
            }
        }

        if (preg_match('@^[A-Za-z]{2}$@', $lang)) {
            return strtolower($lang);
        }

        return '';
    }

    /**
     * if the article has meta description set in the source, use that
     */
    public function getMetaDescription($article) {
        $desc = $this->getMetaContent($article->getDoc(), 'name', 'description');

        if (empty($desc)) {
            $desc = $this->getMetaContent($article->getDoc(), 'property', 'og:description');
        }

        if (empty($desc)) {
            $desc = $this->getMetaContent($article->getDoc(), 'name', 'twitter:description');
        }

        return trim($desc);
    }

    /**
     * if the article has meta keywords set in the source, use that
     */
    public function getMetaKeywords($article) {
        return $this->getMetaContent($article->getDoc(), 'name', 'keywords');
    }

    /**
      * if the article has meta canonical link set in the url
      */
    public function getCanonicalLink($article) {
        $href = '';

        $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'link', 'rel', 'canonical');

        if ($nodes->length) {
            $href = $nodes->item(0)->getAttribute('href');
        }

        if (empty($href)) {
            $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'meta', 'property', 'og:url');

            if ($nodes->length) {
                $href = $nodes->item(0)->getAttribute('href');
            }
        }

        if (empty($href)) {
            $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'meta', 'name', 'twitter:url');

            if ($nodes->length) {
                $href = $nodes->item(0)->getAttribute('href');
            }
        }

        if (!empty($href)) {
            return trim($href);
        } else {
            return $article->getFinalUrl();
        }
    }

    public function getDateFromURL($url) {
        // TODO
    }

    public function getDomain($url) {
        return parse_url($url, PHP_URL_HOST);
    }

    public function extractTags($article) {
        $nodes = $article->getDoc()->filter(self::$A_REL_TAG_SELECTOR);

        $tags = [];

        foreach ($nodes as $node) {
            $tags[] = $node->textContent;
        }

        return $tags;
    }

    /**
     * we're going to start looking for where the clusters of paragraphs are. We'll score a cluster based on the number of stopwords
     * and the number of consecutive paragraphs together, which should form the cluster of text that this node is around
     * also store on how high up the paragraphs are, comments are usually at the bottom and should get a lower score
     *
     * // todo refactor this long method
     * @return
     */
    public function calculateBestNodeBasedOnClustering($article) {
        $doc = $article->getDoc();
        $topNode = null;
        $nodesToCheck = $doc->filter(self::$TOP_NODE_TAGS);
        $startingBoost = 1.0;
        $cnt = 0;
        $i = 0;
        $parentNodes = [];
        $nodesWithText = [];

        foreach ($nodesToCheck as $node) {
            $nodeText = $node->textContent;
            $wordStats = $this->config->getStopWords()->getStopwordCount($nodeText);
            $highLinkDensity = $this->isHighLinkDensity($node);

            if ($wordStats->getStopWordCount() > 2 && !$highLinkDensity) {
                $nodesWithText[] = $node;
            }
        }

        $numberOfNodes = count($nodesWithText);
        $negativeScoring = 0;
        $bottomNodesForNegativeScore = $numberOfNodes * 0.25;

        foreach ($nodesWithText as $node) {
            $boostScore = 0.0;

            if ($this->isOkToBoost($node)) {
                if ($cnt >= 0) {
                    $boostScore = (1.0 / $startingBoost) * 50;
                    $startingBoost += 1;
                }

                if ($numberOfNodes > 15) {
                    if ($numberOfNodes - $i <= $bottomNodesForNegativeScore) {
                        $booster = $bottomNodesForNegativeScore - ($numberOfNodes - $i);
                        $boostScore = pow($booster, 2) * -1;
                        $negscore = abs($boostScore) + $negativeScoring;
                        if ($negscore > 40) {
                            $boostScore = 5;
                        }
                    }
                }

                $nodeText = $node->textContent;
                $wordStats = $this->config->getStopWords()->getStopwordCount($nodeText);
                $upscore = $wordStats->getStopWordCount() + $boostScore;

                $this->updateScore($node->parentNode, $upscore);
                $this->updateScore($node->parentNode->parentNode, $upscore / 2);
                $this->updateNodeCount($node->parentNode, 1);
                $this->updateNodeCount($node->parentNode->parentNode, 1);

                if (!in_array($node->parentNode, $parentNodes)) {
                    $parentNodes[] = $node->parentNode;
                }

                if (!in_array($node->parentNode->parentNode, $parentNodes)) {
                    $parentNodes[] = $node->parentNode->parentNode;
                }

                $cnt++;
                $i++;
            }
        }

        $topNodeScore = 0;
        foreach ($parentNodes as $e) {
            $score = $this->getScore($e);

            if ($score > $topNodeScore) {
                $topNode = $e;
                $topNodeScore = $score;
            }

            if ($topNode === false) {
                $topNode = $e;
            }
        }

        if ($topNode && $this->getScore($topNode) < 20) {
            return null;
        }

        return $topNode;
    }

    /**
     * alot of times the first paragraph might be the caption under an image so we'll want to make sure if we're going to
     * boost a parent node that it should be connected to other paragraphs, at least for the first n paragraphs
     * so we'll want to make sure that the next sibling is a paragraph and has at least some substatial weight to it
     *
     *
     * @param node
     * @return
     */
    private function isOkToBoost($node) {
        $para = 'p';
        $stepsAway = 0;
        $minimumStopWordCount = 5;
        $maxStepsAwayFromNode = 3;

        $siblings = $node->getSiblings();

        foreach ($siblings as $currentNode) {
            if ($currentNode->nodeName == 'p' || $currentNode->nodeName == 'strong') {
                if ($stepsAway >= $maxStepsAwayFromNode) {
                    Debug::trace($this->logPrefix, "Next paragraph is too far away, not boosting");

                    return false;
                }

                $paraText = $currentNode->textContent;
                $wordStats = $this->config->getStopWords()->getStopwordCount($paraText);

                if ($wordStats->getStopWordCount() > $minimumStopWordCount) {
                    Debug::trace($this->logPrefix, "We're gonna boost this node, seems contenty");

                    return true;
                }

                $stepsAway += 1;
            }
        }

        return false;
    }

    public function getShortText($e, $max) {
        if (mb_strlen($e) > $max) {
            return mb_substr($e, 0, $max) . '...';
        } else {
            return $e;
        }
    }

    /**
     * checks the density of links within a node, is there not much text and most of it contains linky shit?
     * if so it's no good
     *
     * @param e
     * @return
     */
    private function isHighLinkDensity(DOMElement $e, $limit = 1.0) {
        $links = $e->filter('a, [onclick]');

        if ($links->length == 0) {
            return false;
        }

        $text = trim($e->textContent);
        $words = explode(' ', $text);
        $numberOfWords = count($words);

        $sb = [];
        foreach ($links as $link) {
            $sb[] = $link->textContent;
        }

        $linkText = implode('', $sb);
        $linkWords = explode(' ', $linkText);
        $numberOfLinkWords = count($linkWords);
        $numberOfLinks = $links->length;
        $linkDivisor = $numberOfLinkWords / $numberOfWords;
        $score = $linkDivisor * $numberOfLinks;

        Debug::trace($this->logPrefix, "Calulated link density score as: " . $score . " for node: " . $this->getShortText($e->textContent, 50));

        if ($score >= $limit) {
            return true;
        }

        return false;
    }

    /**
     * returns the gravityScore as an integer from this node
     *
     * @param node
     * @return
     */
    private function getScore($node) {
        return (int)$node->getAttribute('gravityScore');
    }

    /**
     * adds a score to the gravityScore Attribute we put on divs
     * we'll get the current score then add the score we're passing in to the current
     *
     * @param node
     * @param addToScore - the score to add to the node
     */
    private function updateScore($node, $addToScore) {
        $currentScore = (int)$node->getAttribute('gravityScore');

        $node->setAttribute('gravityScore', $currentScore + $addToScore);
    }

    /**
     * stores how many decent nodes are under a parent node
     *
     * @param node
     * @param addToCount
     */
    private function updateNodeCount($node, $addToCount) {
        $currentScore = (int)$node->getAttribute('gravityNodes');

        $node->setAttribute('gravityNodes', $currentScore + $addToCount);
    }

    /**
     * pulls out videos we like
     *
     * @return
     */
    public function extractVideos($node) {
        $candidates = [];
        $goodMovies = [];
        $youtubeStr = 'youtube';
        $vimdeoStr = 'vimeo';
        $bliptvStr = 'blip';
        $flickrStr = 'flickr';
        $veohStr = 'veoh';
        $dailymotionStr = 'dailymotion';

        if (!($node instanceof \DOMNode)) {
            return [];
        }

        foreach ($node->parentNode->filter('embed, object, iframe') as $e) {
            $candidates[] = $e;
        }

        Debug::trace($this->logPrefix, "extractVideos: Starting to extract videos. Found: " . count($candidates));

        foreach ($candidates as $el) {
            $attr = $el->getAttribute('src');

            if (mb_strpos($attr, $youtubeStr) !== false ||
                mb_strpos($attr, $vimdeoStr) !== false ||
                mb_strpos($attr, $bliptvStr) !== false ||
                mb_strpos($attr, $flickrStr) !== false ||
                mb_strpos($attr, $veohStr) !== false ||
                mb_strpos($attr, $dailymotionStr) !== false) {
                Debug::trace($this->logPrefix, "This page has a video!: " . $attr);

                $goodMovies[] = $el->getAttribute('src');
            }
        }

        Debug::trace($this->logPrefix, "extractVideos: done looking videos");

        return $goodMovies;
    }

    /**
     * pulls out links we like
     *
     * @return
     */
    public function extractLinks(\DOMNode $node) {
        $goodLinks = [];

        $candidates = $node->parentNode->filter('a[href]');

        foreach ($candidates as $el) {
            if ($el->getAttribute('href') != '#' && trim($el->getAttribute('href')) != '') {
                $goodLinks[] = [
                    'url' => $el->getAttribute('href'),
                    'text' => $el->textContent,
                ];
            }
        }

        return $goodLinks;
    }

    public function isTableTagAndNoParagraphsExist(\DOMNode $e) {
        $subParagraphs = $e->filter('p, strong');

        foreach ($subParagraphs as $p) {
            if (mb_strlen($p->textContent) < 25) {
                $p->parentNode->removeChild($p);
            }
        }

        $subParagraphs2 = $e->filter('p');

        if ($subParagraphs2->length == 0 && $e->nodeName != 'td') {
            if ($e->nodeName == 'ul' || $e->nodeName == 'ol') {
                $linkTextLength = array_sum(array_map(function($value){
                    return mb_strlen($value->textContent);
                }, $e->filter('a')));

                $elementTextLength = mb_strlen($e->textContent);

                if ($elementTextLength > 0 && ($linkTextLength / $elementTextLength) < 0.5) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * remove any divs that looks like non-content, clusters of links, or paras with no gusto
     *
     * @param targetNode
     * @return
     */
    public function postExtractionCleanup($targetNode) {
        Debug::trace($this->logPrefix, "Starting cleanup Node");

        if (!($targetNode instanceof \DOMNode)) {
            return null;
        }

        $node = $this->addSiblings($targetNode);

        foreach ($node->childNodes as $e) {
            if ($e->nodeName != 'p' && $e->nodeName != 'strong') {

                if (method_exists($e, 'getAttribute')) {
                    Debug::trace($this->logPrefix, "CLEANUP  NODE: " . $e->getAttribute('id') . " class: " . $e->getAttribute('class'));
                }

                if ($this->isHighLinkDensity($e)
                    || $this->isTableTagAndNoParagraphsExist($e)
                    || !$this->isNodeScoreThreshholdMet($node, $e)) {
                    $e->parentNode->removeChild($e);
                }
            }
        }

        return $node;
    }

    public function isNodeScoreThreshholdMet($node, $e) {
        $topNodeScore = $this->getScore($node);
        $currentNodeScore = $this->getScore($e);
        $thresholdScore = ($topNodeScore * 0.08);

        Debug::trace($this->logPrefix, "topNodeScore: " . $topNodeScore . " currentNodeScore: " . $currentNodeScore . " threshold: " . $thresholdScore);

        if ($currentNodeScore < $thresholdScore && $e->nodeName != 'td') {
            Debug::trace($this->logPrefix, "Removing node due to low threshold score");

            return false;
        } else {
            Debug::trace($this->logPrefix, "Not removing TD node");

            return true;
        }
    }

    /**
     * adds any siblings that may have a decent score to this node
     *
     * @param currentSibling
     * @return
     */
    public function getSiblingContent($currentSibling, $baselineScoreForSiblingParagraphs) {
        if ($currentSibling->nodeType != XML_ELEMENT_NODE) {
            return [];
        } else if (($currentSibling->nodeName == 'p' || $currentSibling->nodeName == 'strong') && !empty($currentSibling->textContent)) {
            return [$currentSibling];
        } else {
            $potentialParagraphs = $currentSibling->filter('p, strong');

            if ($potentialParagraphs->length == 0) {
                return [];
            } else {
                $paragraphs = [];

                foreach ($potentialParagraphs as $firstParagraph) {
                    if (!empty($firstParagraph->textContent)) {
                        $wordStats = $this->config->getStopWords()->getStopwordCount($firstParagraph->textContent);
                        $paragraphScore = $wordStats->getStopWordCount();
                        $siblingBaseLineScore = 0.30;

                        if (($baselineScoreForSiblingParagraphs * $siblingBaseLineScore) < $paragraphScore) {
                            Debug::trace($this->logPrefix, "This node looks like a good sibling, adding it");

                            $paragraphs[] = $firstParagraph->ownerDocument->createElement('p', $firstParagraph->textContent);
                        }
                    }
                }

                return $paragraphs;
            }
        }
    }

    private function addSiblings($topNode) {
        Debug::trace($this->logPrefix, "Starting to add siblings");

        $baselineScoreForSiblingParagraphs = $this->getBaselineScoreForSiblings($topNode);

        foreach ($topNode->getSiblings() as $currentNode) {
            $results = $this->getSiblingContent($currentNode, $baselineScoreForSiblingParagraphs);

            foreach ($results as $result) {
                $topNode->insertBefore($result, $topNode->firstChild);
            }
        }

        return $topNode;
    }

    /**
     * we could have long articles that have tons of paragraphs so if we tried to calculate the base score against
     * the total text score of those paragraphs it would be unfair. So we need to normalize the score based on the average scoring
     * of the paragraphs within the top node. For example if our total score of 10 paragraphs was 1000 but each had an average value of
     * 100 then 100 should be our base.
     *
     * @param topNode
     * @return
     */
    private function getBaselineScoreForSiblings($topNode) {
        $base = 100000;
        $numberOfParagraphs = 0;
        $scoreOfParagraphs = 0;
        $nodesToCheck = $topNode->filter('p, strong');

        foreach ($nodesToCheck as $node) {
            $nodeText = $node->textContent;
            $wordStats = $this->config->getStopWords()->getStopwordCount($nodeText);
            $highLinkDensity = $this->isHighLinkDensity($node);

            if ($wordStats->getStopWordCount() > 2 && !$highLinkDensity) {
                $numberOfParagraphs += 1;
                $scoreOfParagraphs += $wordStats->getStopWordCount();
            }
        }

        if ($numberOfParagraphs > 0) {
            $base = $scoreOfParagraphs / $numberOfParagraphs;
            Debug::trace($this->logPrefix, "The base score for siblings to beat is: " . $base . " NumOfParas: " . $numberOfParagraphs . " scoreOfAll: " . $scoreOfParagraphs);
        }

        return $base;
    }

    public function getPopularWords($cleanedText, $limit = 5)
    {
        $minFrequency = 2;

        $string = trim(preg_replace('/ss+/i', '', $cleanedText));
        $string = preg_replace('/[^a-zA-Z -]/', '', $string); // only take alphabet characters, but keep the spaces and dashes too

        preg_match_all('/\b.*?\b/i', $string, $matchWords);
        $matchWords = $matchWords[0];

        $stopWords = & $this->config->getStopWords()->getCurrentStopWords();

        foreach ($matchWords as $key => &$item) {
            if ($item == '' || in_array(strtolower($item), $stopWords) || strlen($item) <= 3 ) {
                unset($matchWords[$key]);
            }
        }

        $wordCount = str_word_count( implode(" ", $matchWords) , 1);
        $frequency = array_count_values($wordCount);
        arsort($frequency);

        $keywords = [];

        foreach ($frequency as $word => &$freq) {
            if ($freq >= $minFrequency) {
                $keywords[$word] = $freq;
            }

            if (count($keywords) >= $limit) break;
        }

        return $keywords;
    }
}
