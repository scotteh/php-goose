<?php

namespace Goose\DOM;

use Symfony\Component\CssSelector\CssSelector;

/**
 * DOM Document
 *
 * @package Goose\DOM
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class DOMDocument extends \DOMDocument
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
     * @param string $selector
     *
     * @return \DOMNodeList
     */
    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this);

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
     * @param string $selector
     *
     * @return DOMElement[]
     */
    public function filterXPathAsArray($selector) {
        $results = $this->filterXPath($selector);

        $items = [];

        foreach ($results as $key => $item) {
            $items[$key] = $item;
        }

        return $items;
    }
}
