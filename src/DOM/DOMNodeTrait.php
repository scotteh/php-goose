<?php

namespace Goose\DOM;

/**
 * DOM Node Trait
 *
 * @package Goose\DOM
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait DOMNodeTrait
{
    /**
     * @see http://php.net/manual/en/dom.constants.php $nodeType values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return \DOMNode|null
     */
    public function previous($nodeType = null) {
        for ($sibling = $this; ($sibling = $sibling->previousSibling) !== null;) {
            if (is_null($nodeType) || $sibling->nodeType == $nodeType) {
                return $sibling;
            }
        }

        return null;
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $nodeType values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return DOMNodeList
     */
    public function previousAll($nodeType = null) {
        $nodes = new DOMNodeList();

        for ($sibling = $this; ($sibling = $sibling->previousSibling) !== null;) {
            if (is_null($nodeType) || $sibling->nodeType == $nodeType) {
                $nodes[] = $sibling;
            }
        }

        return $nodes->reverse();
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $nodeType values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return \DOMNode|null
     */
    public function next($nodeType = null) {
        for ($sibling = $this; ($sibling = $sibling->nextSibling) !== null;) {
            if (is_null($nodeType) || $sibling->nodeType == $nodeType) {
                return $sibling;
            }
        }

        return null;
    }

    /**
     * @see http://php.net/manual/en/dom.constants.php $nodeType values - XML_*_NODE constants
     *
     * @param int|null $nodeType 
     *
     * @return DOMNodeList
     */
    public function nextAll($nodeType = null) {
        $nodes = new DOMNodeList();

        for ($sibling = $this; ($sibling = $sibling->nextSibling) !== null;) {
            if (is_null($nodeType) || $sibling->nodeType == $nodeType) {
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
     * @return DOMElement
     */
    public function parent() {
        if ($this->parentNode instanceof \DOMDocument) {
            return null;
        }

        return $this->parentNode;
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
            $nodes = new DOMNodeList([$this]);
        }

        $nodes->remove();

        return $this;
    }

    /**
     * @param \DOMNode $newNode
     *
     * @return self
     */
    public function replace($newNode) {
        if ($this->parent()) {
            $this->parent()->replaceChild($newNode, $this);
        }

        return $this;
    }

    /**
     * @param \DOMNode|DOMNodeList $nodes
     *
     * @return self
     */
    public function append($nodes) {
        if (!($nodes instanceof DOMNodeList)) {
            $nodes = new DOMNodeList([$nodes]);
        }

        foreach ($nodes as $node) {
            $this->appendChild($node);
        }

        return $this;
    }
}