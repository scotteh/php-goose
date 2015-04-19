<?php
/**
 * Document Cleaner Interface
 *
 * @package  Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Goose\Cleaners;

use Goose\Article;
use Goose\Configuration;

interface DocumentCleanerInterface {
    public function __construct(Configuration $config);

    /**
     * Clean the contents of the supplied article document
     *
     * @param Goose\Article $article
     *
     * @return Goose\DOM\DOMDocument $doc
     */
    public function clean(Article $article);
}