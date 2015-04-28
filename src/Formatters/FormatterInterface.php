<?php

namespace Goose\Formatters;

use Goose\Article;
use Goose\Configuration;

/**
 * Formatter Interface
 *
 * @package Goose\Formatters
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
interface FormatterInterface {
    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config);

    /**
     * @param Article $article
     */
    public function format(Article $article);
}
