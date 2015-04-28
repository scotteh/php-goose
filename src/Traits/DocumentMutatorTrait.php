<?php

namespace Goose\Traits;

use Goose\DOM\DOMDocument;

/**
 * Document Mutator Trait
 *
 * @package Goose\Traits
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait DocumentMutatorTrait {
    /** @var DOMDocument */
    protected $document;

    /**
     * @param DOMDocument $document
     *
     * @return DOMDocument|null
     */
    protected function document(DOMDocument $document = null) {
        if ($document === null) {
            return $this->document;
        }

        $this->document = $document;
    }
}
