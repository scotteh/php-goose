<?php
/**
 * Document Cleaner
 *
 * @package  Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Goose\Cleaners;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMDocument;
use Goose\Utils\Debug;

abstract class DocumentCleaner {
    protected $config;

    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    protected $doc;

    protected function document(DOMDocument $doc = null) {
        if ($doc === null) {
            return $this->doc;
        }

        $this->doc = $doc;
    }
}
