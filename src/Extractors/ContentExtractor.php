<?php

namespace Goose\Extractors;

use Goose\Article;
use Goose\Configuration;
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

    /** @var souble */
    private static $SIBLING_BASE_LINE_SCORE = 0.30;

    /** @var Configuration */
    private $config;

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

        $titleText = $nodes->first()->text(DOM_NODE_TEXT_NORMALISED);

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
            return trim($nodes->first()->getAttribute('href'));
        }

        $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'meta', 'property', 'og:url');

        if ($nodes->count()) {
            return trim($nodes->first()->getAttribute('content'));
        }

        $nodes = $this->getNodesByLowercasePropertyValue($article->getDoc(), 'meta', 'name', 'twitter:url');

        if ($nodes->count()) {
            return trim($nodes->first()->getAttribute('content'));
        }

        return $article->getFinalUrl();
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
            $tags[] = $node->text(DOM_NODE_TEXT_NORMALISED);
        }

        return $tags;
    }

    /**
     * @param Article $article
     *
     * @return array
     */
    private function getBestNodeCandidatesByContents(Article $article) {
        $results = [];

        $nodes = $article->getDoc()->filter('p, td, pre');

        foreach ($nodes as $node) {
            $wordStats = $this->config->getStopWords()->getStopwordCount($node->text());
            $highLinkDensity = $this->isHighLinkDensity($node);

            if ($wordStats->getStopWordCount() > 2 && !$highLinkDensity) {
                $results[] = $node;
            }
        }

        return $results;
    }

    /**
     * @param DOMElement $node
     * @param int $i
     * @param int $totalNodes
     *
     * @return double
     */
    private function getBestNodeCandidateScore(DOMElement $node, $i, $totalNodes) {
        $boostScore = (1.0 / ($i + 1)) * 50;
        $bottomNodesForNegativeScore = $totalNodes * 0.25;

        if ($totalNodes > 15) {
            if ($totalNodes - $i <= $bottomNodesForNegativeScore) {
                $booster = $bottomNodesForNegativeScore - ($totalNodes - $i);
                $boostScore = pow($booster, 2) * -1;
                $negscore = abs($boostScore);
                if ($negscore > 40) {
                    $boostScore = 5;
                }
            }
        }

        $wordStats = $this->config->getStopWords()->getStopwordCount($node->text());
        $upscore = $wordStats->getStopWordCount() + $boostScore;

        return $upscore;
    }

    /**
     * @param array $nodes
     *
     * @return DOMElement|null
     */
    private function getBestNodeByScore($nodes) {
        $topNode = null;
        $topNodeScore = 0;

        foreach ($nodes as $node) {
            $score = $this->getScore($node);

            if ($score > $topNodeScore) {
                $topNode = $node;
                $topNodeScore = $score;
            }

            if ($topNode === false) {
                $topNode = $node;
            }
        }

        if ($topNode && $this->getScore($topNode) < 20) {
            return null;
        }

        return $topNode;
    }

    /**
     * @param DOMElement $node
     * @param double $upscore
     */
    private function calculateBestNodeCandidateScores(DOMElement $node, $upscore) {
        if ($node->parent() instanceof DOMElement) {
            $this->updateScore($node->parent(), $upscore);
            $this->updateNodeCount($node->parent(), 1);

            $this->updateScore($node->parent()->parent(), $upscore / 2);
            $this->updateNodeCount($node->parent()->parent(), 1);
        }
    }

    /**
     * @param DOMElement $node
     * @param array $nodeCandidates
     */
    private function updateBestNodeCandidates(DOMElement $node, $nodeCandidates) {
        if (!in_array($node->parent(), $nodeCandidates)) {
            $nodeCandidates[] = $node->parent();
        }

        if ($node->parent() instanceof DOMElement) {
            if (!in_array($node->parent()->parent(), $nodeCandidates)) {
                $nodeCandidates[] = $node->parent()->parent();
            }
        }

        return $nodeCandidates;
    }

    /**
     * We're going to start looking for where the clusters of paragraphs are. We'll score a cluster based on the number of stopwords
     * and the number of consecutive paragraphs together, which should form the cluster of text that this node is around
     * also store on how high up the paragraphs are, comments are usually at the bottom and should get a lower score
     *
     * @param Article $article
     *
     * @return DOMElement|null
     */
    public function getBestNode(Article $article) {
        $nodes = $this->getBestNodeCandidatesByContents($article);

        $nodeCandidates = [];

        $i = 0;
        foreach ($nodes as $node) {
            if ($this->isOkToBoost($node)) {
                $upscore = $this->getBestNodeCandidateScore($node, $i, count($nodes));

                $this->calculateBestNodeCandidateScores($node, $upscore);
                $nodeCandidates = $this->updateBestNodeCandidates($node, $nodeCandidates);

                $i++;
            }
        }

        return $this->getBestNodeByScore($nodeCandidates);
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

        foreach ($siblings as $sibling) {
            if ($sibling->is('p', 'strong')) {
                if ($stepsAway >= $maxStepsAwayFromNode) {
                    return false;
                }

                $wordStats = $this->config->getStopWords()->getStopwordCount($sibling->text());

                if ($wordStats->getStopWordCount() > $minimumStopWordCount) {
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

        $words = preg_split('@[\s]+@iu', $e->text(), -1, PREG_SPLIT_NO_EMPTY);

        $sb = [];
        foreach ($links as $link) {
            $sb[] = $link->text(DOM_NODE_TEXT_NORMALISED);
        }

        $linkText = implode('', $sb);
        $linkWords = explode(' ', $linkText);
        $numberOfLinkWords = count($linkWords);
        $numberOfLinks = $links->count();
        $linkDivisor = $numberOfLinkWords / count($words);
        $score = $linkDivisor * $numberOfLinks;

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
        if ($node instanceof DOMElement) {
            $currentScore = (int)$node->getAttribute('gravityScore');

            $node->setAttribute('gravityScore', $currentScore + $addToScore);
        }
    }

    /**
     * Stores how many decent nodes are under a parent node
     *
     * @param DOMElement $node
     * @param int $addToCount
     */
    private function updateNodeCount(DOMElement $node, $addToCount) {
        if ($node instanceof DOMElement) {
            $currentScore = (int)$node->getAttribute('gravityNodes');

            $node->setAttribute('gravityNodes', $currentScore + $addToCount);
        }
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

        $nodes = $node->parent()->filter('embed, object, iframe');

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

        $candidates = $node->parent()->filter('a[href]');

        foreach ($candidates as $el) {
            if ($el->getAttribute('href') != '#' && trim($el->getAttribute('href')) != '') {
                $goodLinks[] = [
                    'url' => $el->getAttribute('href'),
                    'text' => $el->text(DOM_NODE_TEXT_NORMALISED),
                ];
            }
        }

        return $goodLinks;
    }

    /**
     * @param DOMElement $topNode
     *
     * @return bool
     */
    private function removeSmallParagraphs(DOMElement $topNode) {
        $nodes = $topNode->filter('p, strong');

        foreach ($nodes as $node) {
            if (mb_strlen($node->text(DOM_NODE_TEXT_NORMALISED)) < 25) {
                $node->remove();
            }
        }
    }

    /**
     * @param DOMElement $topNode
     *
     * @return bool
     */
    public function isTableTagAndNoParagraphsExist(DOMElement $topNode) {
        $this->removeSmallParagraphs($topNode);

        $nodes = $topNode->filter('p');

        if ($nodes->count() == 0 && $topNode->is(':not(td)')) {
            if ($topNode->is('ul, ol')) {
                $linkTextLength = array_sum(array_map(function($value){
                    return mb_strlen($value->text(DOM_NODE_TEXT_NORMALISED));
                }, $topNode->filter('a')->toArray()));

                $elementTextLength = mb_strlen($topNode->text(DOM_NODE_TEXT_NORMALISED));

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
     * @param DOMElement $topNode
     */
    public function postExtractionCleanup(DOMElement $topNode) {
        $this->addSiblings($topNode);

        foreach ($topNode->children() as $node) {
            if ($node->is(':not(p):not(strong)')) {
                if ($this->isHighLinkDensity($node)
                    || $this->isTableTagAndNoParagraphsExist($node)
                    || !$this->isNodeScoreThreshholdMet($topNode, $node)) {
                    $node->remove();
                }
            }
        }
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

        if ($currentNodeScore < $thresholdScore && $e->is(':not(td)')) {
            return false;
        }

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
        if ($currentSibling->is('p, strong') && !empty($currentSibling->text(DOM_NODE_TEXT_TRIM))) {
            return [$currentSibling];
        }

        $results = [];

        $nodes = $currentSibling->filter('p, strong');

        foreach ($nodes as $node) {
            $text = $node->text(DOM_NODE_TEXT_TRIM);

            if (!empty($text)) {
                $wordStats = $this->config->getStopWords()->getStopwordCount($text);

                if (($baselineScoreForSiblingParagraphs * self::$SIBLING_BASE_LINE_SCORE) < $wordStats->getStopWordCount()) {
                    $results[] = $node->document()->createElement('p', $text);
                }
            }
        }

        return $results;
    }

    /**
     * @param DOMElement $topNode
     */
    private function addSiblings(DOMElement $topNode) {
        $baselineScoreForSiblingParagraphs = $this->getBaselineScoreForSiblings($topNode);

        foreach ($topNode->previousAll(XML_ELEMENT_NODE) as $currentNode) {
            $results = $this->getSiblingContent($currentNode, $baselineScoreForSiblingParagraphs);

            foreach ($results as $result) {
                $topNode->insertBefore($result, $topNode->firstChild);
            }
        }
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
            $nodeText = $node->text();
            $wordStats = $this->config->getStopWords()->getStopwordCount($nodeText);
            $highLinkDensity = $this->isHighLinkDensity($node);

            if ($wordStats->getStopWordCount() > 2 && !$highLinkDensity) {
                $numberOfParagraphs += 1;
                $scoreOfParagraphs += $wordStats->getStopWordCount();
            }
        }

        if ($numberOfParagraphs > 0) {
            $base = $scoreOfParagraphs / $numberOfParagraphs;
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
