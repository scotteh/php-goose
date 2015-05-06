<?php

namespace Goose;

use Goose\Utils\URLHelper;
use DOMWrap\Document;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Crawler
 *
 * @package Goose
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Crawler {
    /** @var Configuration */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @return Configuration
     */
    public function config() {
        return $this->config;
    }

    /**
     * @param string $url
     * @param string|null $rawHTML
     *
     * @return Article
     */
    public function crawl($url, $rawHTML = null) {
        $article = new Article();

        $parseCandidate = URLHelper::getCleanedUrl($url);

        if (empty($rawHTML)) {
            $rawHTML = $this->getHTML($parseCandidate->url);
        }

        // Generate document
        $doc = $this->getDocument($rawHTML);

        // Set core mutators
        $article->setFinalUrl($parseCandidate->url);
        $article->setDomain($parseCandidate->parts->host);
        $article->setLinkhash($parseCandidate->linkhash);
        $article->setRawHtml($rawHTML);
        $article->setDoc($doc);
        $article->setRawDoc(clone $doc);

        // Pre-extraction document cleaning
        $this->modules('cleaners', $article);

        // Extract content
        $this->modules('extractors', $article);

        // Post-extraction content formatting
        $this->modules('formatters', $article);

        return $article;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getHTML($url) {
        $options = $this->config()->get('browser');

        $guzzle = new GuzzleClient();
        $response = $guzzle->get($url, $options);

        return $response->getBody();
    }

    /**
     * @param string $rawHTML
     *
     * @return Document
     */
    private function getDocument($rawHTML) {
        $doc = new Document();
        $doc->html($rawHTML);

        return $doc;
    }

    /**
     * @param string $category
     * @param Article $article
     */
    public function modules($category, $article) {
        $modules = $this->config->getModules($category);

        foreach ($modules as $module) {
            $obj = new $module($this->config());
            $obj->run($article);
        }
    }
}

