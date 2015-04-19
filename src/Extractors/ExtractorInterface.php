<?php

namespace Goose\Extractors;

use Goose\Article;
use Goose\Configuration;

/**
 * Extractor Interface
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
interface ExtractorInterface {
    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config);

    /**
     * @param Article $article
     */
    public function extract(Article $article);
}
