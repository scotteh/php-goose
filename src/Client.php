<?php

namespace Goose;

class Client {
    protected $config = array();

    public function __construct($config = array()) {
        $this->config = $config;
    }

    public function extractContent($url, $rawHTML = null, $config = array()) {
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