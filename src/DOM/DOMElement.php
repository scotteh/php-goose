<?php

namespace Goose\DOM;

use Goose\Utils\Debug;
use Symfony\Component\CssSelector\CssSelector;

class DOMElement extends \DOMElement
{
    public function filter($selector) {
        if (!class_exists('Symfony\\Component\\CssSelector\\CssSelector')) {
            throw new \RuntimeException('Unable to filter with a CSS selector as the Symfony CssSelector is not installed (you can use filterXPath instead).');
        }

        return $this->filterXPath(CssSelector::toXPath($selector));
    }

    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this->ownerDocument);

        return $domxpath->query($xpath, $this);
    }

    public function getSiblings() {
        $currentSibling = $this->previousSibling;
        $b = [];

        while ($currentSibling != null) {
            Debug::trace(null, "SIBLINGCHECK: " . $this->debugNode($currentSibling));

            $b[] = $currentSibling;

            $currentSibling = $currentSibling->previousSibling;
        }

        return $b;
    }

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
