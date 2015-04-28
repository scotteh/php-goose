<?php

namespace Goose\Extractors;

use Goose\Article;
use Goose\Configuration;

/**
 * Abstract Extractor
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class AbstractExtractor {
    /** @var Configuration */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @param Article $article
     */
    abstract public function extract(Article $article);
}
