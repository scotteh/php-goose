<?php

namespace Goose\DOM;

use Countable;
use ArrayAccess;
use RecursiveIterator;
use RecursiveIteratorIterator;
use Traversable;

/**
 * DOM Node List
 *
 * @package Goose\DOM
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class DOMNodeList implements Countable, ArrayAccess, RecursiveIterator
{
    /** @var array */
    private $nodes = [];

    /**
     * @param Traversable|array $nodes
     */
    public function __construct($nodes = []) {
        foreach ($nodes as $node) {
            $this->nodes[] = $node;
        }
    }

    /**
     * @see Countable::count()
     *
     * @return int
     */
    public function count() {
        return count($this->nodes);
    }

    /**
     * @see ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->nodes[$offset]);
    }

    /**
     * @see ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset) {
        return isset($this->nodes[$offset]) ? $this->nodes[$offset] : null;
    }

    /**
     * @see ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->nodes[] = $value;
        } else {
            $this->nodes[$offset] = $value;
        }
    }

    /**
     * @see ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->nodes[$offset]);
    }

    /**
     * @see RecursiveIterator::RecursiveIteratorIterator()
     *
     * @return RecursiveIteratorIterator
     */
    public function getRecursiveIterator() {
        return new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * @see RecursiveIterator::getChildren()
     *
     * @return RecursiveIterator
     */
    public function getChildren() {
        $nodes = [];

        if ($this->valid()) {
            $nodes = $this->current()->childNodes;
        }

        return new static($nodes);
    }

    /**
     * @see RecursiveIterator::hasChildren()
     *
     * @return bool
     */
    public function hasChildren() {
        if ($this->valid()) {
            return $this->current()->hasChildNodes();
        }

        return false;
    }

    /**
     * @see RecursiveIterator::current()
     * @see Iterator::current()
     *
     * @return mixed
     */
    public function current() {
        return current($this->nodes);
    }

    /**
     * @see RecursiveIterator::key()
     * @see Iterator::key()
     *
     * @return mixed
     */
    public function key() {
        return key($this->nodes);
    }

    /**
     * @see RecursiveIterator::next()
     * @see Iterator::next()
     *
     * @return mixed
     */
    public function next() {
        return next($this->nodes);
    }

    /**
     * @see RecursiveIterator::rewind()
     * @see Iterator::rewind()
     *
     * @return mixed
     */
    public function rewind() {
        return reset($this->nodes);
    }

    /**
     * @see RecursiveIterator::valid()
     * @see Iterator::valid()
     *
     * @return bool
     */
    public function valid() {
        return key($this->nodes) !== null;
    }

    /**
     * @return mixed
     *
     * @return DOMNodeList
     */
    public function reverse() {
        array_reverse($this->nodes);

        return $this;
    }

    /**
     * @return mixed
     */
    public function first() {
        return $this->rewind();
    }

    /**
     * @return mixed
     */
    public function last() {
        return end($this->nodes);
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function get($key) {
        if (isset($this->nodes[$key])) {
            return $this->nodes[$key];
        }

        return null;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value) {
        $this->nodes[$key] = $value;
    }

    /**
     * @return mixed
     */
    public function toArray() {
        return $this->nodes;
    }

    /**
     * @return self
     */
    public function remove() {
        foreach ($this->nodes as $node) {
            if ($node instanceof \DOMNode
              && $node->parentNode instanceof \DOMNode) {
                $node->parentNode->removeChild($node);
            }
        }

        $this->nodes = [];

        return $this;
    }

    /**
     * @param DOMNodeList|array $elements
     *
     * @return DOMNodeList
     */
    public function merge($elements = []) {
        if (!is_array($elements)) {
            $elements = $elements->toArray();
        }

        return new static(array_merge($this->toArray(), $elements));
    }
}