<?php

namespace Goose\OutputFormatters;

use Goose\Configuration;
use Goose\DOM\DOMText;
use Goose\DOM\DOMElement;

/**
 * Output Formatter
 *
 * @package Goose\OutputFormatters
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class OutputFormatter {
    /** @var Configuration */
    private $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * Removes all unnecessary elements and formats the selected text nodes
     *
     * @param DOMElement $topNode The top most node to format
     *
     * @return string Formatted string with all HTML removed
     */
    public function getFormattedText(DOMElement $topNode) {
        $this->removeNodesWithNegativeScores($topNode);
        $this->convertLinksToText($topNode);
        $this->replaceTagsWithText($topNode);
        $this->removeParagraphsWithFewWords($topNode);

        return $this->convertToText($topNode);
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
     * @param DOMElement $topNode The top most node to format
     *
     * @return string Formatted string with all HTML
     */
    public function cleanupHtml(DOMElement $topNode) {
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
            $paragraphs = $topNode->filter('p');

            foreach ($paragraphs as $el) {
                $stopWords = $this->config->getStopWords()->getStopwordCount($el->text());

                if (mb_strlen($el->text(DOM_NODE_TEXT_NORMALISED)) < 8 && $stopWords->getStopWordCount() < 3 && $el->filter('object')->count() == 0 && $el->filter('embed')->count() == 0) {
                    $el->remove();
                }
            }

            /** @todo Implement */
        }
    }
}
