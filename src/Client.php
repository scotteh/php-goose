<?php

namespace Goose;

class Client {
    protected $config = [];

    public function __construct($config = []) {
        $this->config = $config;
    }

    public function extractContent($url, $rawHTML = null, $config = []) {
        $config = new Configuration(array_merge($this->config, $config));

        $crawler = new Crawler($config);
        $article = $crawler->crawl((object)[
            'config' => $config,
            'rawHTML' => $rawHTML,
            'url' => $url,
        ]);

        return $article;
    }
}