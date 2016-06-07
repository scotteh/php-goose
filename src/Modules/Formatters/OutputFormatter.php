<?php

namespace Goose\Modules\Formatters;

use Goose\Article;
use Goose\Utils\Helper;
use Goose\Traits\NodeCommonTrait;
use Goose\Traits\NodeGravityTrait;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\AbstractModule;
use Goose\Modules\ModuleInterface;
use DOMWrap\Text;
use DOMWrap\Element;

/**
 * Output Formatter
 *
 * @package Goose\Modules\Formatters
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class OutputFormatter extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait, NodeGravityTrait, NodeCommonTrait;

    /** @var double */
    protected static $SIBLING_BASE_LINE_SCORE = 0.30;

    /** @var string */
    protected static $CLEANUP_IGNORE_SELECTOR = ':not(p):not(strong):not(h1):not(h2):not(h3):not(h4):not(h5):not(h6)';

    /**
     * @param Article $article
     */
    public function run(Article $article) {
        $this->article($article);

        if ($this->article()->getTopNode() instanceof Element) {
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
     * @param Element $topNode The top most node to format
     *
     * @return string
     */
    private function convertToText(Element $topNode) {
        if (empty($topNode)) {
            return '';
        }

        $list = [];
        foreach ($topNode->contents() as $child) {
            $list[] = trim($child->text());
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
     * @param Element $topNode
     *
     * @return string
     */
    private function convertToHtml(Element $topNode) {
        if (empty($topNode)) {
            return '';
        }

        return $topNode->ownerDocument->saveHTML($topNode);
    }

    /**
     * cleans up and converts any nodes that should be considered text into text
     *
     * @param Element $topNode
     */
    private function convertLinksToText(Element $topNode) {
        if (!empty($topNode)) {
            $links = $topNode->find('a');

            foreach ($links as $item) {
                $images = $item->find('img');

                if ($images->count() == 0) {
                    $item->replaceWith(new Text(Helper::textNormalise($item->text())));
                }
            }
        }
    }

    /**
     * if there are elements inside our top node that have a negative gravity score, let's
     * give em the boot
     *
     * @param Element $topNode
     */
    private function removeNodesWithNegativeScores(Element $topNode) {
        if (!empty($topNode)) {
            $gravityItems = $topNode->find('*[gravityScore]');

            foreach ($gravityItems as $item) {
                $score = (int)$item->attr('gravityScore');

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
     * @param Element $topNode
     */
    private function replaceTagsWithText(Element $topNode) {
        if (!empty($topNode)) {
            $items = $topNode->find('b, strong, i');

            foreach ($items as $item) {
                $item->replaceWith(new Text($this->getTagCleanedText($item)));
            }
        }
    }

    /**
     * @todo Implement
     *
     * @param Element $item
     *
     * @return string
     */
    private function getTagCleanedText(Element $item) {
        return Helper::textNormalise($item->text());
    }

    /**
     * remove paragraphs that have less than x number of words, would indicate that it's some sort of link
     *
     * @param Element $topNode
     */
    private function removeParagraphsWithFewWords(Element $topNode) {
        if (!empty($topNode)) {
            $nodes = $topNode->find('p');

            foreach ($nodes as $node) {
                $stopWords = $this->config()->getStopWords()->getStopwordCount($node->text());

                if (mb_strlen(Helper::textNormalise($node->text())) < 8 && $stopWords->getStopWordCount() < 3 && $node->find('object')->count() == 0 && $node->find('embed')->count() == 0) {
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

        foreach ($this->article()->getTopNode()->contents() as $node) {
            if ($node->is(self::$CLEANUP_IGNORE_SELECTOR)) {
                if ($this->isHighLinkDensity($node)
                    || $this->isTableTagAndNoParagraphsExist($node)
                    || !$this->isNodeScoreThreshholdMet($this->article()->getTopNode(), $node)) {
                    $node->remove();
                }
            }
        }
    }

    /**
     * @param Element $topNode
     */
    private function removeSmallParagraphs(Element $topNode) {
        $nodes = $topNode->find('p, strong');

        foreach ($nodes as $node) {
            if (mb_strlen(Helper::textNormalise($node->text())) < 25) {
                $node->remove();
            }
        }
    }

    /**
     * @param Element $topNode
     *
     * @return bool
     */
    private function isTableTagAndNoParagraphsExist(Element $topNode) {
        $this->removeSmallParagraphs($topNode);

        $nodes = $topNode->find('p');

        if ($nodes->count() == 0 && $topNode->is(':not(td)')) {
            if ($topNode->is('ul, ol')) {
                $linkTextLength = array_sum(array_map(function($value) {
                    return mb_strlen(Helper::textNormalise($value->text()));
                }, $topNode->find('a')->toArray()));

                $elementTextLength = mb_strlen(Helper::textNormalise($topNode->text()));

                if ($elementTextLength > 0 && ($linkTextLength / $elementTextLength) < 0.5) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param Element $topNode
     * @param Element $node
     *
     * @return bool
     */
    private function isNodeScoreThreshholdMet(Element $topNode, Element $node) {
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
     * @param Element $currentSibling
     * @param int $baselineScoreForSiblingParagraphs
     *
     * @return Element[]
     */
    private function getSiblingContent(Element $currentSibling, $baselineScoreForSiblingParagraphs) {
        $text = trim($currentSibling->text());

        if ($currentSibling->is('p, strong') && !empty($text)) {
            return [$currentSibling];
        }

        $results = [];

        $nodes = $currentSibling->find('p, strong');

        foreach ($nodes as $node) {
            $text = trim($node->text());

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
     * @param Element $topNode
     */
    private function addSiblings(Element $topNode) {
        $baselineScoreForSiblingParagraphs = $this->getBaselineScoreForSiblings($topNode);

        $previousSiblings = $topNode->precedingAll(function($node) {
            return $node instanceof Element;
        });

        // Find all previous sibling element nodes
        foreach ($previousSiblings as $siblingNode) {
            $results = $this->getSiblingContent($siblingNode, $baselineScoreForSiblingParagraphs);

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
     * @param Element $topNode
     *
     * @return int
     */
    private function getBaselineScoreForSiblings(Element $topNode) {
        $base = 100000;
        $numberOfParagraphs = 0;
        $scoreOfParagraphs = 0;
        $nodesToCheck = $topNode->find('p, strong');

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
