<?php

namespace Goose\Extractors;

use Goose\Article;

/**
 * Publish Date Extractor
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class PublishDateExtractor extends Extractor implements ExtractorInterface {
    /**
     * @param Article $article
     */
    public function extract(Article $article) {
        return null;
    }
}
