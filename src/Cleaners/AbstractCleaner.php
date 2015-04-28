<?php

namespace Goose\Cleaners;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMDocument;

/**
 * Abstract Cleaner
 *
 * @package Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class AbstractCleaner {
    /** @var Configuration */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }
}
