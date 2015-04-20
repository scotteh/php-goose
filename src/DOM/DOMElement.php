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
    /**
     * @param string $selector
     *
     * @return \DOMNodeList
     */
    public function filter($selector) {
        return $this->filterXPath(CssSelector::toXPath($selector));
    }

    /**
     * @param string $xpath
     *
     * @return \DOMNodeList
     */
    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this->ownerDocument);

        return $domxpath->query($xpath, $this);
    }

    /**
     * @param string $selector
     *
     * @return DOMElement[]
     */
    public function filterAsArray($selector) {
        $results = $this->filter($selector);

        $items = [];

        foreach ($results as $key => $item) {
            $items[$key] = $item;
        }

        return $items;
    }

    /**
     * @param string $xpath
     *
     * @return DOMElement[]
     */
    public function filterXPathAsArray($xpath) {
        $results = $this->filterXPath($xpath);

        $items = [];

        foreach ($results as $key => $item) {
            $items[$key] = $item;
        }

        return $items;
    }

    /**
     * @return DOMElement[]
     */
    public function siblings() {
        $currentSibling = $this->previousSibling;
        $b = [];

        while ($currentSibling != null) {
            Debug::trace(null, "SIBLINGCHECK: " . $this->debugNode($currentSibling));

            $b[] = $currentSibling;

            $currentSibling = $currentSibling->previousSibling;
        }

        return $b;
    }

    /**
     * DOMNodeList is only array like. Removing items using foreach() has undesired results.
     *
     * @return DOMElement[]
     */
    public function children() {
        $children = [];

        foreach ($this->childNodes as $node) {
            $children[] = $node;
        }

        return $children;
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
