<?php

namespace Goose\Traits;

use DOMWrap\Document;

/**
 * Document Mutator Trait
 *
 * @package Goose\Traits
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait DocumentMutatorTrait {
    /** @var Document */
    protected $document;

    /**
     * @param Document $document
     *
     * @return Document|null
     */
    protected function document(Document $document = null) {
        if ($document === null) {
            return $this->document;
        }

        $this->document = $document;
    }
}
