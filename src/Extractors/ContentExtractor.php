<?php

namespace Goose\Extractors;

use Goose\Article;
use Goose\Configuration;
use Goose\Utils\Debug;
use Goose\DOM\DOMDocument;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMNodeList;

/**
 * Content Extractor
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 * @todo Review DOMElement type hinting, \DOMNode elements may be called on these methods (though they shouldn't be).
 */
class ContentExtractor {
    /** @var string[] */
    private static $SPLITTER_CHARS = [
        '|', '-', '»', ':',
    ];

    /** @var string */
    private static $A_REL_TAG_SELECTOR = "a[rel='tag'], a[href*='/tag/']";

    /** @var string */
    private static $TOP_NODE_TAGS = 'p, td, pre';

    /** @var string[] */
    private static $VIDEO_PROVIDERS = [
        'youtube\.com',
        'youtu\.be',
        'vimeo\.com',
        'blip\.tv',
        'dailymotion\.com',
        'dai\.ly',
        'flickr\.com',
        'flic\.kr',
    ];

    /** @var Configuration */
    private $config;

    /** @var string */
    private $logPrefix = 'ContentExtractor: ';

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @param Article $article
     *
     * @return string
     */
    public function getTitle(Article $article) {
        $nodes = $article->getDoc()->filter('html > head > title');

        if (!$nodes->count()) return '';

        $titleText = $nodes->first()->textContent;

        foreach (self::$SPLITTER_CHARS as $char) {
            if (strpos($titleText, $char) !== false) {
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

        return trim($titleText);
    }

    /**
     * @param DOMDocument $doc
     * @param string $tag
     * @param string $property
     * @param string $value
     *
     * @return DOMNodeList
     */
    private function getNodesByLowercasePropertyValue(DOMDocument $doc, $tag, $property, $value) {
        return $doc->filterXPath("descendant-or-self::".$tag."[translate(@".$property.", 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='".$value."']");
    }

    /**
     * @param DOMDocument $doc
     * @param string $property
     * @param string $value
     * @param string $attr
     *
     * @return string
     */
    private function getMetaContent(DOMDocument $doc, $property, $value, $attr = 'content') {
        $nodes = $this->getNodesByLowercasePropertyValue($doc, 'meta', $property, $value);

        if (!$nodes->count()) {
            return '';
        }

        $content = $nodes->first()->getAttribute($attr);
        $content = trim($content);

        return $content;
    }

    /**
     * If the article has meta language set in the source, use that
     *
     * @param Article $article
     *
     * @return string
     */
    public function getMetaLanguage(Article $article) {
        $lang = '';

        $el = $article->getDoc()->filter('html[lang]');

        if ($el->count()) {
            $lang = $el->first()->getAttribute('lang');
        }

        if (empty($lang)) {
            $selectors = [
                'html > head > meta[http-equiv=content-language]',
                'html > head > meta[name=lang]',
            ];

            foreach ($selectors as $selector) {
                $el = $article->getDoc()->filter($selector);

                if ($el->count()) {
                    $lang = $el->first()->getAttribute('content');
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
     * If the article has meta description set in the source, use that
     *
     * @param Article $article
     *
     * @return string
     */
    public function getMetaDescription(Article $article) {
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
     * If the article has meta keywords set in the source, use that
     *
     * @param Article $article
     *
     * @return string
     */
    public function getMetaKeywords(Article $article) {
        return $this->getMetaContent($article->getDoc(), 'name', 'keywords');
    }

    /**
     * If the article has meta canonical link set in the url
     *
     * @param Article $article
     *
     * @return string
     */
    public function getCanonicalLink(Article $article) {
        $href = '';

        $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'link', 'rel', 'canonical');

        if ($nodes->count()) {
            $href = $nodes->first()->getAttribute('href');
        }

        if (empty($href)) {
            $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'meta', 'property', 'og:url');

            if ($nodes->count()) {
                $href = $nodes->first()->getAttribute('content');
            }
        }

        if (empty($href)) {
            $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'meta', 'name', 'twitter:url');

            if ($nodes->count()) {
                $href = $nodes->first()->getAttribute('content');
            }
        }

        if (!empty($href)) {
            return trim($href);
        } else {
            return $article->getFinalUrl();
        }
    }

    /**
     * @todo
     *
     * @param string $url
     *
     * @return string
     */
    public function getDateFromURL($url) {
        // TODO
        return '';
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getDomain($url) {
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * @param Article $article
     *
     * @return string[]
     */
    public function extractTags(Article $article) {
        $nodes = $article->getDoc()->filter(self::$A_REL_TAG_SELECTOR);

        $tags = [];

        foreach ($nodes as $node) {
            $tags[] = $node->textContent;
        }

        return $tags;
    }

    /**
     * We're going to start looking for where the clusters of paragraphs are. We'll score a cluster based on the number of stopwords
     * and the number of consecutive paragraphs together, which should form the cluster of text that this node is around
     * also store on how high up the paragraphs are, comments are usually at the bottom and should get a lower score
     *
     * @todo Re-factor this long method
     *
     * @param Article $article
     *
     * @return DOMElement|null
     */
    public function calculateBestNodeBasedOnClustering(Article $article) {
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

            if ($node->nodeType == XML_ELEMENT_NODE && $this->isOkToBoost($node)) {
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
     * A lot of times the first paragraph might be the caption under an image so we'll want to make sure if we're going to
     * boost a parent node that it should be connected to other paragraphs, at least for the first n paragraphs
     * so we'll want to make sure that the next sibling is a paragraph and has at least some substantial weight to it
     *
     * @param DOMElement $node
     *
     * @return bool
     */
    private function isOkToBoost(DOMElement $node) {
        $stepsAway = 0;
        $minimumStopWordCount = 5;
        $maxStepsAwayFromNode = 3;

        $siblings = $node->previousAll(XML_ELEMENT_NODE);

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

    /**
     * @param string $e
     * @param int $max
     *
     * @return string
     */
    public function getShortText($e, $max) {
        if (mb_strlen($e) > $max) {
            return mb_substr($e, 0, $max) . '...';
        } else {
            return $e;
        }
    }

    /**
     * Checks the density of links within a node, is there not much text and most of it contains linky shit?
     * if so it's no good
     *
     * @param DOMElement $e
     * @param double $limit
     *
     * @return bool
     */
    private function isHighLinkDensity(DOMElement $e, $limit = 1.0) {
        $links = $e->filter('a, [onclick]');

        if ($links->count() == 0) {
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
        $numberOfLinks = $links->count();
        $linkDivisor = $numberOfLinkWords / $numberOfWords;
        $score = $linkDivisor * $numberOfLinks;

        Debug::trace($this->logPrefix, "Calulated link density score as: " . $score . " for node: " . $this->getShortText($e->textContent, 50));

        if ($score >= $limit) {
            return true;
        }

        return false;
    }

    /**
     * Returns the gravityScore as an integer from this node
     *
     * @param DOMElement $node
     *
     * @return int
     */
    private function getScore(DOMElement $node) {
        return (int)$node->getAttribute('gravityScore');
    }

    /**
     * Adds a score to the gravityScore Attribute we put on divs
     * we'll get the current score then add the score we're passing in to the current
     *
     * @param DOMElement $node
     * @param int $addToScore
     */
    private function updateScore(DOMElement $node, $addToScore) {
        $currentScore = (int)$node->getAttribute('gravityScore');

        $node->setAttribute('gravityScore', $currentScore + $addToScore);
    }

    /**
     * Stores how many decent nodes are under a parent node
     *
     * @param DOMElement $node
     * @param int $addToCount
     */
    private function updateNodeCount(DOMElement $node, $addToCount) {
        $currentScore = (int)$node->getAttribute('gravityNodes');

        $node->setAttribute('gravityNodes', $currentScore + $addToCount);
    }

    /**
     * Pulls out videos we like
     *
     * @param DOMElement $node
     *
     * @return string[]
     */
    public function extractVideos(DOMElement $node) {
        $movies = [];

        $nodes = $node->parentNode->filter('embed, object, iframe');

        foreach ($nodes as $node) {
            if ($node->hasAttribute('src')) {
                $src = $node->getAttribute('src');
            } else {
                $src = $node->getAttribute('data');
            }

            $match = array_reduce(self::$VIDEO_PROVIDERS, function($match, $domain) use ($src) {
                $srcHost = parse_url($src, PHP_URL_HOST);
                $srcScheme = parse_url($src, PHP_URL_SCHEME);

                return $match || preg_match('@' . $domain . '$@i', $srcHost) && in_array($srcScheme, ['http', 'https']);
            });

            if ($match) {
                $movies[] = $src;
            }
        }

        return $movies;
    }

    /**
     * Pulls out links we like
     *
     * @param DOMElement $node
     *
     * @return string[]
     */
    public function extractLinks(DOMElement $node) {
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

    /**
     * @param DOMElement $e
     *
     * @return bool
     */
    public function isTableTagAndNoParagraphsExist(DOMElement $e) {
        $subParagraphs = $e->filter('p, strong');

        foreach ($subParagraphs as $p) {
            if (mb_strlen($p->textContent) < 25) {
                $p->parentNode->removeChild($p);
            }
        }

        $subParagraphs2 = $e->filter('p');

        if ($subParagraphs2->count() == 0 && $e->nodeName != 'td') {
            if ($e->nodeName == 'ul' || $e->nodeName == 'ol') {
                $linkTextLength = array_sum(array_map(function($value){
                    return mb_strlen($value->textContent);
                }, $e->filter('a')->toArray()));

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
     * Remove any divs that looks like non-content, clusters of links, or paras with no gusto
     *
     * @param DOMElement $targetNode
     *
     * @return DOMElement
     */
    public function postExtractionCleanup(DOMElement $targetNode) {
        Debug::trace($this->logPrefix, "Starting cleanup Node");

        $node = $this->addSiblings($targetNode);

        foreach ($node->childNodes as $e) {
            if ($e->nodeType == XML_ELEMENT_NODE && $e->nodeName != 'p' && $e->nodeName != 'strong') {

                Debug::trace($this->logPrefix, "CLEANUP  NODE: " . $e->getAttribute('id') . " class: " . $e->getAttribute('class'));

                if ($this->isHighLinkDensity($e)
                    || $this->isTableTagAndNoParagraphsExist($e)
                    || !$this->isNodeScoreThreshholdMet($node, $e)) {
                    $e->parentNode->removeChild($e);
                }
            }
        }

        return $node;
    }

    /**
     * @param DOMElement $node
     * @param DOMElement $e
     *
     * @return bool
     */
    public function isNodeScoreThreshholdMet(DOMElement $node, DOMElement $e) {
        $topNodeScore = $this->getScore($node);
        $currentNodeScore = $this->getScore($e);
        $thresholdScore = ($topNodeScore * 0.08);

        Debug::trace($this->logPrefix, "topNodeScore: " . $topNodeScore . " currentNodeScore: " . $currentNodeScore . " threshold: " . $thresholdScore);

        if ($currentNodeScore < $thresholdScore && $e->nodeName != 'td') {
            Debug::trace($this->logPrefix, "Removing node due to low threshold score");

            return false;
        }

        Debug::trace($this->logPrefix, "Not removing TD node");

        return true;
    }

    /**
     * Adds any siblings that may have a decent score to this node
     *
     * @param DOMElement $currentSibling
     * @param int $baselineScoreForSiblingParagraphs
     *
     * @return DOMElement[]
     */
    public function getSiblingContent(DOMElement $currentSibling, $baselineScoreForSiblingParagraphs) {
        if ($currentSibling->nodeType != XML_ELEMENT_NODE) {
            return [];
        } else if (($currentSibling->nodeName == 'p' || $currentSibling->nodeName == 'strong') && !empty($currentSibling->textContent)) {
            return [$currentSibling];
        }

        $potentialParagraphs = $currentSibling->filter('p, strong');

        if ($potentialParagraphs->count() == 0) {
            return [];
        }

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

    /**
     * @param DOMElement $topNode
     *
     * @return DOMElement
     */
    private function addSiblings(DOMElement $topNode) {
        Debug::trace($this->logPrefix, "Starting to add siblings");

        $baselineScoreForSiblingParagraphs = $this->getBaselineScoreForSiblings($topNode);

        foreach ($topNode->previousAll(XML_ELEMENT_NODE) as $currentNode) {
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
     * @param DOMElement $topNode
     *
     * @return int
     */
    private function getBaselineScoreForSiblings(DOMElement $topNode) {
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

    /**
     * @param Article $article
     *
     * @return string[]
     */
    public function getPopularWords(Article $article) {
        $limit = 5;
        $minimumFrequency = 1;
        $stopWords = $this->config->getStopWords()->getCurrentStopWords();

        $text = $article->getTitle();
        $text .= ' ' . $article->getMetaDescription();

        if ($article->getTopNode()) {
            $text .= ' ' . $article->getCleanedArticleText();
        }

        // Decode and split words by white-space
        $text = html_entity_decode($text, ENT_COMPAT | ENT_HTML5, 'UTF-8');
        $words = preg_split('@[\s]+@iu', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Determine stop words currently in $words
        $ignoreWords = array_intersect($words, $stopWords);
        // Remove ignored words from $words
        $words = array_diff($words, $ignoreWords);

        // Count and sort $words
        $words = array_count_values($words);
        arsort($words);

        // Limit and filter $words
        $words = array_slice($words, 0, $limit);
        $words = array_filter($words, function($value) use ($minimumFrequency){
            return !($value < $minimumFrequency);
        });

        return $words;
    }
}
