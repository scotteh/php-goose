<?php

namespace Goose\Traits;

use Goose\DOM\DOMElement;

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
}
