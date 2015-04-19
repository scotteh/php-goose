<?php

namespace Goose\Extractors;

use Goose\Article;

/**
 * Additional Data Extractor
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class AdditionalDataExtractor extends Extractor implements ExtractorInterface {
    /**
     * @param Article $article
     */
    public function extract(Article $article) {
        return null;
    }
}
