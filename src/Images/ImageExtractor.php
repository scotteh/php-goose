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
     */
    abstract public function getBestImage($article);
}
