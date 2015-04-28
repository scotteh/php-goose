<?php

namespace Goose\Traits;

use Goose\DOM\DOMElement;

/**
 * Node Common Trait
 *
 * @package Goose\Traits
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait NodeCommonTrait {
    /**
     * Checks the density of links within a node, is there not much text and most of it contains linky shit?
     * if so it's no good
     *
     * @param DOMElement $node
     * @param double $limit
     *
     * @return bool
     */
    private function isHighLinkDensity(DOMElement $node, $limit = 1.0) {
        $links = $node->filter('a, [onclick]');

        if ($links->count() == 0) {
            return false;
        }

        $words = preg_split('@[\s]+@iu', $node->text(), -1, PREG_SPLIT_NO_EMPTY);

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
}
