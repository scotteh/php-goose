<?php

namespace Goose\DOM;

use Symfony\Component\CssSelector\CssSelector;

/**
 * DOM Filter Trait
 *
 * @package Goose\DOM
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait DOMFilterTrait {
    /**
     * @param string $selector
     *
     * @return DOMNodeList
     */
    public function filter($selector) {
        return new DOMNodeList($this->filterXPath(CssSelector::toXPath($selector)));
    }

    /**
     * @param string $xpath
     *
     * @return DOMNodeList
     */
    public function filterXPath($xpath) {
        $domxpath = new \DOMXPath($this->document());

        return new DOMNodeList($domxpath->query($xpath, $this));
    }

    /**
     * @return DOMDocument
     */
    public function document() {
        return $this;
    }
}