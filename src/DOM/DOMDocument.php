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
    use DOMFilterTrait;
}
