<?php

namespace Goose\Images;

/**
 * Image Extractor
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class ImageExtractor {
    /**
     * @param \Goose\Article $article
     *
     * @return Image|null
     */
    abstract public function getBestImage(\Goose\Article $article);
}
