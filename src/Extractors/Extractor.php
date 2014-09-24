<?php

namespace Goose\Extractors;

class Extractor {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }
}
