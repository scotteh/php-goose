<?php

namespace Goose\Formatters;

use Goose\Article;
use Goose\Configuration;

/**
 * Abstract Formatter
 *
 * @package Goose\Formatters
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class AbstractFormatter {
    /** @var Configuration */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }
}
