<?php

namespace Goose\Images;

use Goose\Article;
use Goose\Configuration;

/**
 * Extractor Interface
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
     *
     * @return Image|null
     */
    public function getBestImage(Article $article);
}
