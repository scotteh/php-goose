<?php

namespace Goose\Modules\Formatters;

use Goose\Article;
use Goose\DOM\DOMText;
use Goose\DOM\DOMElement;
use Goose\Traits\NodeCommonTrait;
use Goose\Traits\NodeGravityTrait;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\AbstractModule;
use Goose\Modules\ModuleInterface;

/**
 * Output Formatter
 *
 * @package Goose\Modules\Formatters
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class OutputFormatter extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait, NodeGravityTrait, NodeCommonTrait;

    /**
     * @param Article $article
     */
    public function run(Article $article) {
        $this->article($article);

        if ($this->article()->getTopNode() instanceof DOMElement) {
            $this->postExtractionCleanup();

            $article->setCleanedArticleText($this->getFormattedText());
            $article->setHtmlArticle($this->cleanupHtml());
        }
    }

    /**
     * Removes all unnecessary elements and formats the selected text nodes
     *
     * @return string Formatted string with all HTML removed
     */
    private function getFormattedText() {
        $this->removeNodesWithNegativeScores($this->article()->getTopNode());
        $this->convertLinksToText($this->article()->getTopNode());
        $this->replaceTagsWithText($this->article()->getTopNode());
        $this->removeParagraphsWithFewWords($this->article()->getTopNode());

        return $this->convertToText($this->article()->getTopNode());
    }

    /**
     * Takes an element and turns the P tags into \n\n
     *
     * @param DOMElement $topNode The top most node to format
     *
     * @return string
     */
    private function convertToText(DOMElement $topNode) {
        if (empty($topNode)) {
            return '';
        }

        $list = [];
        foreach ($topNode->children() as $child) {
            $list[] = $child->text(DOM_NODE_TEXT_TRIM);
        }

        return implode("\n\n", $list);
    }

    /**
     * Scrape the node content and return the html
     *
     * @return string Formatted string with all HTML
     */
    private function cleanupHtml() {
        $topNode = $this->article()->getTopNode();

        if (empty($topNode)) {
            return '';
        }

        $this->removeParagraphsWithFewWords($topNode);

        $html = $this->convertToHtml($topNode);

        return str_replace(['<p></p>', '<p>&nbsp;</p>'], '', $html);
    }

    /**
     * @param DOMElement $topNode
     *
     * @return string
     */
    private function convertToHtml(DOMElement $topNode) {
        if (empty($topNode)) {
            return '';
        }

        return $topNode->ownerDocument->saveHTML($topNode);
    }

    /**
     * cleans up and converts any nodes that should be considered text into text
     *
     * @param DOMElement $topNode
     */
    private function convertLinksToText(DOMElement $topNode) {
        if (!empty($topNode)) {
            $links = $topNode->filter('a');

            foreach ($links as $item) {
                $images = $item->filter('img');

                if ($images->count() == 0) {
                    $item->replace(new DOMText($item->text(DOM_NODE_TEXT_NORMALISED)));
                }
            }
        }
    }

    /**
     * if there are elements inside our top node that have a negative gravity score, let's
     * give em the boot
     *
     * @param DOMElement $topNode
     */
    private function removeNodesWithNegativeScores(DOMElement $topNode) {
        if (!empty($topNode)) {
            $gravityItems = $topNode->filter('*[gravityScore]');

            foreach ($gravityItems as $item) {
                $score = (int)$item->getAttribute('gravityScore');

                if ($score < 1) {
                    $item->remove();
                }
            }
        }
    }

    /**
     * replace common tags with just text so we don't have any crazy formatting issues
     * so replace <br>, <i>, <strong>, etc.... with whatever text is inside them
     *
     * @param DOMElement $topNode
     */
    private function replaceTagsWithText(DOMElement $topNode) {
        if (!empty($topNode)) {
            $items = $topNode->filter('b, strong, i');

            foreach ($items as $item) {
                $item->replace(new DOMText($this->getTagCleanedText($item)));
            }
        }
    }

    /**
     * @todo Implement
     *
     * @param DOMElement $item
     *
     * @return string
     */
    private function getTagCleanedText(DOMElement $item) {
        return $item->text(DOM_NODE_TEXT_NORMALISED);
    }

    /**
     * remove paragraphs that have less than x number of words, would indicate that it's some sort of link
     *
     * @param DOMElement $topNode
     */
    private function removeParagraphsWithFewWords(DOMElement $topNode) {
        if (!empty($topNode)) {
            $nodes = $topNode->filter('p');

            foreach ($nodes as $node) {
                $stopWords = $this->config()->getStopWords()->getStopwordCount($node->text());

                if (mb_strlen($node->text(DOM_NODE_TEXT_NORMALISED)) < 8 && $stopWords->getStopWordCount() < 3 && $node->filter('object')->count() == 0 && $node->filter('embed')->count() == 0) {
                    $node->remove();
                }
            }

            /** @todo Implement */
        }
    }

    /**
     * Remove any divs that looks like non-content, clusters of links, or paras with no gusto
     */
    private function postExtractionCleanup() {
        $this->addSiblings($this->article()->getTopNode());

        foreach ($this->article()->getTopNode()->children() as $node) {
            if ($node->is(':not(p):not(strong)')) {
                if ($this->isHighLinkDensity($node)
                    || $this->isTableTagAndNoParagraphsExist($node)
                    || !$this->isNodeScoreThreshholdMet($this->article()->getTopNode(), $node)) {
                    $node->remove();
                }
            }
        }
    }

    /**
     * @param DOMElement $topNode
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
    private function isTableTagAndNoParagraphsExist(DOMElement $topNode) {
        $this->removeSmallParagraphs($topNode);

        $nodes = $topNode->filter('p');

        if ($nodes->count() == 0 && $topNode->is(':not(td)')) {
            if ($topNode->is('ul, ol')) {
                $linkTextLength = array_sum(array_map(function($value) {
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
     * @param DOMElement $topNode
     * @param DOMElement $node
     *
     * @return bool
     */
    private function isNodeScoreThreshholdMet(DOMElement $topNode, DOMElement $node) {
        $topNodeScore = $this->getScore($topNode);
        $currentNodeScore = $this->getScore($node);
        $thresholdScore = ($topNodeScore * 0.08);

        if ($currentNodeScore < $thresholdScore && $node->is(':not(td)')) {
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
    private function getSiblingContent(DOMElement $currentSibling, $baselineScoreForSiblingParagraphs) {
        $text = $currentSibling->text(DOM_NODE_TEXT_TRIM);

        if ($currentSibling->is('p, strong') && !empty($text)) {
            return [$currentSibling];
        }

        $results = [];

        $nodes = $currentSibling->filter('p, strong');

        foreach ($nodes as $node) {
            $text = $node->text(DOM_NODE_TEXT_TRIM);

            if (!empty($text)) {
                $wordStats = $this->config()->getStopWords()->getStopwordCount($text);

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
            $wordStats = $this->config()->getStopWords()->getStopwordCount($nodeText);
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
}
