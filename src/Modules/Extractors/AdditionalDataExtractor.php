<?php

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\AbstractModule;
use Goose\Modules\ModuleInterface;

/**
 * Additional Data Extractor
 *
 * @package Goose\Modules\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class AdditionalDataExtractor extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait;

    /**
     * @param Article $article
     */
    public function run(Article $article) {
    }
}
