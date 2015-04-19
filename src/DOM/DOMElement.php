<?php

namespace Goose\DOM;

use Goose\Utils\Debug;
use Symfony\Component\CssSelector\CssSelector;

class DOMElement extends \DOMElement
{
    public function filter($selector) {
        return $this->filterXPath(CssSelector::toXPath($selector));
    }

    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this->ownerDocument);

        return $domxpath->query($xpath, $this);
    }

    public function filterAsArray($selector) {
        $results = $this->filter($selector);

        $items = [];

        foreach ($results as $key => $item) {
            $items[$key] = $item;
        }

        return $items;
    }

    public function filterXPathAsArray($selector) {
        $results = $this->filterXPath($selector);

        $items = [];

        foreach ($results as $key => $item) {
            $items[$key] = $item;
        }

        return $items;
    }

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

    // DOMNodeList is only array like. Removing items using foreach() has undesired results.
    public function children() {
        $children = [];

        foreach ($this->childNodes as $node) {
            $children[] = $node;
        }

        return $children;
    }

    /**
     * @codeCoverageIgnore
     */
    private function debugNode($e) {
        $sb = '';

        $sb .= "' nodeName: '";
        $sb .= $e->nodeName;

        if ($e->nodeType == XML_ELEMENT_NODE) {
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
