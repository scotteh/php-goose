<?php

namespace Goose\DOM;

use Goose\Utils\Debug;
use Symfony\Component\CssSelector\CssSelector;

/**
 * DOM Element
 *
 * @package Goose\DOM
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class DOMElement extends \DOMElement
{
    use DOMFilterTrait;

    /**
     * @see DOMFilterTrait::document()
     *
     * @return DOMDocument
     */
    public function document() {
        return $this->ownerDocument;
    }

    /**
     * @param string $selector
     *
     * @return bool
     */
    public function is($selector) {
        $selector = implode(',', array_map(function($str) {
            $str = ltrim($str, " \t\n\r\0\x0B*");

            return '*' . $str;
        }, explode(',', $selector)));

        $nodes = $this->filter($selector);

        return !empty($nodes);
    }

    /**
     * @return DOMNodeList
     */
    public function previousSiblings() {
        $nodes = new DOMNodeList();

        $currentSibling = $this->previousSibling;

        while ($currentSibling != null) {
            Debug::trace(null, "SIBLINGCHECK: " . $this->debugNode($currentSibling));

            $nodes[] = $currentSibling;

            $currentSibling = $currentSibling->previousSibling;
        }

        return $nodes->reverse();
    }

    /**
     * @return DOMNodeList
     */
    public function nextSiblings() {
        $nodes = new DOMNodeList();

        $currentSibling = $this->nextSibling;

        while ($currentSibling != null) {
            Debug::trace(null, "SIBLINGCHECK: " . $this->debugNode($currentSibling));

            $nodes[] = $currentSibling;

            $currentSibling = $currentSibling->nextSibling;
        }

        return $nodes;
    }

    /**
     * @return DOMNodeList
     */
    public function siblings() {
        return $this->previousSiblings()->merge(
            $this->nextSiblings()
        );
    }

    /**
     * DOMNodeList is only array like. Removing items using foreach() has undesired results.
     *
     * @return DOMNodeList
     */
    public function children() {
        return new DOMNodeList($this->childNodes);
    }

    /**
     * @param \DOMNode $e
     *
     * @codeCoverageIgnore
     */
    private function debugNode(\DOMNode $e) {
        $sb = '';

        $sb .= "' nodeName: '";
        $sb .= $e->nodeName;

        if ($e instanceof DOMElement) {
            $sb .= "' GravityScore: '";
            $sb .= $e->getAttribute('gravityScore');
            $sb .= "' paraNodeCount: '";
            $sb .= $e->getAttribute('gravityNodes');
            $sb .= "' nodeId: '";
            $sb .= $e->getAttribute('id');
            $sb .= "' className: '";
            $sb .= $e->getAttribute('class');
        }

        return $sb;
    }
}
