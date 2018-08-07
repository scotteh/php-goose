<?php declare(strict_types=1);

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
     * Returns the gravityScore from this node
     *
     * @param Element $node
     *
     * @return float
     */
    private function getScore(Element $node): float {
        return (float)$node->attr('gravityScore');
    }

    /**
     * Adds a score to the gravityScore Attribute we put on divs
     * we'll get the current score then add the score we're passing in to the current
     *
     * @param Element $node
     * @param float $addToScore
     */
    private function updateScore(Element $node, float $addToScore): void {
        $currentScore = (float)$node->attr('gravityScore');

        $node->attr('gravityScore', (string)($currentScore + $addToScore));
    }

    /**
     * Stores how many decent nodes are under a parent node
     *
     * @param Element $node
     * @param int $addToCount
     */
    private function updateNodeCount(Element $node, int $addToCount): void {
        $currentScore = (int)$node->attr('gravityNodes');

        $node->attr('gravityNodes', (string)($currentScore + $addToCount));
    }
}
