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
     * @param int|null $nodeType 
     *
     * @return \DOMNode|null
     */
    public function previous($type = null) {
        for ($sibling = $this; ($sibling = $sibling->previousSibling) !== null;) {
            if (is_null($type) || $sibling->nodeType == $type) {
                return $sibling;
            }
        }

        return null;
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $type values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return DOMNodeList
     */
    public function previousAll($type = null) {
        $nodes = new DOMNodeList();

        for ($sibling = $this; ($sibling = $sibling->previousSibling) !== null;) {
            if (is_null($type) || $sibling->nodeType == $type) {
                $nodes[] = $sibling;
            }
        }

        return $nodes->reverse();
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $type values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return \DOMNode|null
     */
    public function next($type = null) {
        for ($sibling = $this; ($sibling = $sibling->nextSibling) !== null;) {
            if (is_null($type) || $sibling->nodeType == $type) {
                return $sibling;
            }
        }

        return null;
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $type values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return DOMNodeList
     */
    public function nextAll($type = null) {
        $nodes = new DOMNodeList();

        for ($sibling = $this; ($sibling = $sibling->nextSibling) !== null;) {
            if (is_null($type) || $sibling->nodeType == $type) {
                $nodes[] = $sibling;
            }
        }

        return $nodes;
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $type values - XML_*_NODE constants
     *
     * @return DOMNodeList
     */
    public function siblings() {
        return $this->previousAll()->merge(
            $this->nextAll()
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
     * @param string|null $selector
     *
     * @return self
     */
    public function remove($selector = null) {
        if (!is_null($selector)) {
            /** @todo Make this relative to the current node */
            $nodes = $this->filter($selector);
        } else {
            $nodes = new DOMNodeList($this);
        }

        $nodes->remove();

        return $this;
    }
}
