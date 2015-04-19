<?php

namespace Goose\Extractors;

use Goose\Article;
use Goose\Configuration;

interface ExtractorInterface {
    public function __construct(Configuration $config);

    public function extract(Article $article);
}
