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

    public function __construct($version = null, $encoding = null) {
        parent::__construct($version, $encoding);

        $this->registerNodeClass('DOMText', 'Goose\\DOM\\DOMText');
        $this->registerNodeClass('DOMElement', 'Goose\\DOM\\DOMElement');
        $this->registerNodeClass('DOMComment', 'Goose\\DOM\\DOMComment');
    }

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

    /**
     * @param string $html
     *
     * @return self
     */
    public function html($html, $options = 0) {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $fn = function($matches) {
            return (
                isset($matches[1])
                ? '</script> -->'
                : '<!-- <script>'
            );
        };

        $html = preg_replace_callback('@<([/])?script[^>]*>@Ui', $fn, $html);

        if (mb_detect_encoding($html, mb_detect_order(), true) === 'UTF-8') {
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        }

        $this->loadHTML($html, $options);

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        return $this;
    }
}
