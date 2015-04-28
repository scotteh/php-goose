<?php

namespace Goose\Images;

use Goose\Article;
use Goose\Configuration;

/**
 * Abstract Image Extractor
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class AbstractImageExtractor {
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
     *
     * @return Image|null
     */
    abstract public function getBestImage(Article $article);
}
