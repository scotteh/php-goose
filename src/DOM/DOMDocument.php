<?php

namespace Goose\DOM;

/**
 * DOM Document
 *
 * @package Goose\DOM
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class DOMDocument extends \DOMDocument
{
    use DOMNodeTrait;

    /**
     * @see DOMNodeTrait::document()
     *
     * @return DOMDocument
     */
    public function document() {
        return $this;
    }

    /**
     * @see DOMNodeTrait::parent()
     *
     * @return DOMElement
     */
    public function parent() {
        return null;
    }

    /**
     * @see DOMNodeTrait::replace()
     *
     * @param \DOMNode $newNode
     *
     * @return self
     */
    public function replace($newNode) {
        $this->replaceChild($newNode, $this);

        return $this;
    }
}
