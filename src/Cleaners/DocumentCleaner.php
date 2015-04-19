<?php

namespace Goose\Cleaners;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMDocument;
use Goose\Utils\Debug;

/**
 * Document Cleaner
 *
 * @package Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class DocumentCleaner {
    /** @var Configuration */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /** @var DOMDocument */
    protected $doc;

    /**
     * @param DOMDocument $doc
     *
     * @return DOMDocument|null
     */
    protected function document(DOMDocument $doc = null) {
        if ($doc === null) {
            return $this->doc;
        }

        $this->doc = $doc;
    }
}
