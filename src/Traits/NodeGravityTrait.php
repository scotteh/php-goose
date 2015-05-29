<?php

namespace Goose\Traits;

use DOMWrap\Element;

/**
 * Node Gravity Trait
 *
 * @package Goose\Traits
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait NodeGravityTrait {
    /**
     * Returns the gravityScore as an integer from this node
     *
     * @param Element $node
     *
     * @return int
     */
    private function getScore(Element $node) {
        return (int)$node->attr('gravityScore');
    }

    /**
     * Adds a score to the gravityScore Attribute we put on divs
     * we'll get the current score then add the score we're passing in to the current
     *
     * @param Element $node
     * @param int $addToScore
     */
    private function updateScore(Element $node, $addToScore) {
        $currentScore = (int)$node->attr('gravityScore');

        $node->attr('gravityScore', $currentScore + $addToScore);
    }

    /**
     * Stores how many decent nodes are under a parent node
     *
     * @param Element $node
     * @param int $addToCount
     */
    private function updateNodeCount(Element $node, $addToCount) {
        $currentScore = (int)$node->attr('gravityNodes');

        $node->attr('gravityNodes', $currentScore + $addToCount);
    }
}
