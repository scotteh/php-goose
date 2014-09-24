<?php

namespace Goose;

class Client {
    protected $config = array();

    public function __construct($config = array()) {
        $this->config = new Configuration($config);
    }

    public function extractContent($url, $rawHTML = null) {
        $crawler = new Crawler($this->config);
        $article = $crawler->crawl((object)[
            'config' => $this->config,
            'url' => $url,
            'rawHTML' => $rawHTML,
        ]);

        return $article;
    }
}