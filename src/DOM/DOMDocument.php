<?php

namespace Goose\DOM;

use Symfony\Component\CssSelector\CssSelector;

class DOMDocument extends \DOMDocument
{
    public function filter($selector) {
        if (!class_exists('Symfony\\Component\\CssSelector\\CssSelector')) {
            throw new \RuntimeException('Unable to filter with a CSS selector as the Symfony CssSelector is not installed (you can use filterXPath instead).');
        }

        return $this->filterXPath(CssSelector::toXPath($selector));
    }

    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this);

        return $domxpath->query($xpath, $this);
    }
}
