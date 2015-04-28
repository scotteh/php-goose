<?php

namespace Goose;

use GuzzleHttp\Client as GuzzleClient;
use Goose\Utils\URLHelper;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;
use Goose\Images\Image;
use Goose\Images\ImageExtractor;
use Goose\Extractors\MetaExtractor;
use Goose\Extractors\PublishDateExtractor;
use Goose\Extractors\ExtractorInterface;
use Goose\Cleaners\DocumentCleaner;
use Goose\Formatters\OutputFormatter;

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
        $this->getDocumentCleaner()->clean($article);

        // Extract content
        $this->getContentExtractor()->extract($article);
        $this->getImageExtractor()->extract($article);
        $this->getMetaExtractor()->extract($article);
        $this->getPublishDateExtractor()->extract($article);
        //$this->getAdditionalDataExtractor()->extract($article);

        // Post-extraction content formatting
        $this->getOutputFormatter()->format($article);

        return $article;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getHTML($url) {
        $config = $this->config->getGuzzle();

        if (!is_array($config)) {
            $config = [];
        }

        $guzzle = new GuzzleClient();
        $response = $guzzle->get($url, $config);

        return $response->getBody();
    }

    /**
     * @return ImageExtractor
     */
    private function getImageExtractor() {
        return new ImageExtractor($this->config);
    }

    /**
     * @return OutputFormatter
     */
    private function getOutputFormatter() {
        return new OutputFormatter($this->config);
    }

    /**
     * @return DocumentCleaner
     */
    private function getDocumentCleaner() {
        return new DocumentCleaner($this->config);
    }

    /**
     * @param string $rawHTML
     *
     * @return DOMDocument
     */
    private function getDocument($rawHTML) {
        $doc = new DOMDocument();
        $doc->html($rawHTML);

        return $doc;
    }

    /**
     * @return ContentExtractor
     */
    private function getContentExtractor() {
        return $this->config->getContentExtractor();
    }

    /**
     * @return MetaExtractor
     */
    private function getMetaExtractor() {
        return new MetaExtractor($this->config);
    }

    /**
     * @return PublishDateExtractor
     */
    private function getPublishDateExtractor() {
        return new PublishDateExtractor($this->config);
    }
}

