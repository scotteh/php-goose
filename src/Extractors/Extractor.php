<?php

namespace Goose\Extractors;

use Goose\Configuration;

/**
 * Extractor
 *
 * @package Goose\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Extractor {
    /** @var Configuration */
    private $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }
}
