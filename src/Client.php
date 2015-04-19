<?php

namespace Goose;

/**
 * Client
 *
 * @package Goose
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Client {
    /** @var Configuration */
    protected $config = [];

    /**
     * @param Configuration $config
     */
    public function __construct($config = []) {
        $this->config = $config;
    }

    /**
     * @param string $url
     * @param string $rawHTML
     * @param mixed[] $config
     */
    public function extractContent($url, $rawHTML = null, $config = []) {
        $config = new Configuration(array_merge($this->config, $config));

        $crawler = new Crawler($config);
        $article = $crawler->crawl($url, $rawHTML);

        return $article;
    }
}