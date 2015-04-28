<?php

namespace Goose\Extractors;

use Goose\Article;
use Goose\Traits\ArticleMutatorTrait;

/**
 * Additional Data Extractor
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class AdditionalDataExtractor extends AbstractExtractor implements ExtractorInterface {
    use ArticleMutatorTrait;

    /**
     * @param Article $article
     */
    public function extract(Article $article) {
    }
}
