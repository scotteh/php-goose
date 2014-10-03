<?php

namespace Goose\OutputFormatters;

use Goose\Utils\Debug;

class OutputFormatter {
    private $logPrefix = 'OutputFormatter: ';

    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Removes all unnecessarry elements and formats the selected text nodes
     * @param topNode the top most node to format
     * @return a formatted string with all HTML removed
     */
    public function getFormattedText($topNode) {
        $this->removeNodesWithNegativeScores($topNode);
        $this->convertLinksToText($topNode);
        $this->replaceTagsWithText($topNode);
        $this->removeParagraphsWithFewWords($topNode);
        return $this->convertToText($topNode);
    }

    /**
     * Depricated use {@link #getFormattedText(Element)}
     * takes an element and turns the P tags into \n\n
     *
     * @return
     */
    private function convertToText($topNode) {
        if (empty($topNode)) {
            return '';
        }

        $list = [];
        foreach ($topNode->childNodes as $child) {
            $list[] = trim($child->textContent);
        }

        return implode("\n\n", $list);
    }

    /**
     * Scape the node content and return the html
     * @param topNode the top most node to format
     * @return a formatted string with all HTML
     */
    public function cleanupHtml($topNode) {
        if (empty($topNode)) {
            return '';
        }

        $this->removeParagraphsWithFewWords($topNode);

        return $this->convertToHtml($topNode);
    }

    private function convertToHtml($topNode) {
        if (empty($topNode)) {
            return '';
        }

        return $topNode->ownerDocument->saveHTML($topNode);
    }

    /**
     * cleans up and converts any nodes that should be considered text into text
     */
    private function convertLinksToText($topNode) {
        if (!empty($topNode)) {
            $links = $topNode->filter('a');

            foreach ($links as $item) {
                $images = $item->filter('img');

                if (!count($images)) {
                    $item->parentNode->replaceChild(new \DOMText($item->textContent), $item);
                }
            }
        }
    }

    /**
     * if there are elements inside our top node that have a negative gravity score, let's
     * give em the boot
     */
    private function removeNodesWithNegativeScores($topNode) {
        if (!empty($topNode)) {
            $gravityItems = $topNode->filter('*[gravityScore]');

            foreach ($gravityItems as $item) {
                $score = (int)$item->getAttribute('gravityScore');

                if ($score < 1) {
                    $item->parentNode->removeChild($item);
                }
            }
        }
    }

    /**
     * replace common tags with just text so we don't have any crazy formatting issues
     * so replace <br>, <i>, <strong>, etc.... with whatever text is inside them
     */
    private function replaceTagsWithText($topNode) {
        if (!empty($topNode)) {
            $items = $topNode->filter('b, strong, i');

            foreach ($items as $item) {
                $item->parentNode->replaceChild(new \DOMText($this->getTagCleanedText($item)), $item);
            }
        }
    }

    private function getTagCleanedText($item) {
        // TODO
        return $item->textContent;
    }

    /**
     * remove paragraphs that have less than x number of words, would indicate that it's some sort of link
     */
    private function removeParagraphsWithFewWords($topNode) {
        if (!empty($topNode)) {
            $paragraphs = $topNode->filter('p');

            foreach ($paragraphs as $el) {
                $stopWords = $this->config->getStopWords()->getStopwordCount($el->textContent);
 
                if (mb_strlen($el->textContent) < 8 && $stopWords->getStopWordCount() < 3 && count($el->filter('object')) == 0 && count($el->filter('embed')) == 0) {
                    $el->parentNode->removeChild($el);
                }
            }

            // TODO
        }
    }
}
