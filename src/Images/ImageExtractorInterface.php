<?php

namespace Goose\Images;

use Goose\Article;
use Goose\Configuration;

/**
 * Image Extractor Interface
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
interface ImageExtractorInterface {
    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config);

    /**
     * @param Article $article
     */
    public function extract(Article $article);
}
