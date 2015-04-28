<?php

namespace Goose\Cleaners;

use Goose\Article;
use Goose\Configuration;

/**
 * Cleaner Interface
 *
 * @package Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
interface CleanerInterface {
    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config);

    /**
     * Clean the contents of the supplied article document
     *
     * @param Article $article
     */
    public function clean(Article $article);
}