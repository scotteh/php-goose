<?php

namespace Goose\Extractors;

use Goose\Configuration;

class Extractor {
    private $config;

    public function __construct(Configuration $config) {
        $this->config = $config;
    }
}
