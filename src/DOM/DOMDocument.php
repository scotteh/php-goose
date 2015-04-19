<?php

namespace Goose\DOM;

use Symfony\Component\CssSelector\CssSelector;

class DOMDocument extends \DOMDocument
{
    public function filter($selector) {
        return $this->filterXPath(CssSelector::toXPath($selector));
    }

    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this);

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
}
