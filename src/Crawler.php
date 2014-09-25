<?php

namespace Goose;

use GuzzleHttp\Client as GuzzleClient;
use Goose\Utils\Debug;
use Goose\Utils\URLHelper;
use Goose\DOM\DOMDocument;
use Goose\Cleaners\StandardDocumentCleaner;
use Goose\OutputFormatters\OutputFormatter;

class Crawler {
    protected $config;

    public function __construct($config = array()) {
        $this->config = $config;
    }

    public function crawl($crawlCandidate) {
        $article = new Article();

        $parseCandidate = URLHelper::getCleanedUrl($crawlCandidate->url);
        $rawHtml = $this->getHTML($crawlCandidate, $parseCandidate);
        $doc = $this->getDocument($parseCandidate->url, $rawHtml);

        $extractor = $this->getExtractor();
        $docCleaner = $this->getDocCleaner();
        $outputFormatter = $this->getOutputFormatter();

        $article->setFinalUrl($parseCandidate->url);
        $article->setDomain($parseCandidate->parts->host);
        $article->setLinkhash($parseCandidate->linkhash);
        $article->setRawHtml($rawHtml);
        $article->setDoc($doc);
        $article->setRawDoc(clone $doc);

        $article->setTitle($extractor->getTitle($article));
        $article->setPublishDate($this->config->getPublishDateExtractor($doc));
        $article->setAdditionalData($this->config->getAdditionalDataExtractor($doc));
        $article->setMetaDescription($extractor->getMetaDescription($article));
        $article->setMetaKeywords($extractor->getMetaKeywords($article));
        $article->setCanonicalLink($extractor->getCanonicalLink($article));
        $article->setTags($extractor->extractTags($article));
        $article->setDoc($docCleaner->clean($article));

        $article->setTopNode($extractor->calculateBestNodeBasedOnClustering($article));
        $article->setMovies($extractor->extractVideos($article->getTopNode()));
        $article->setTopNode($extractor->postExtractionCleanup($article->getTopNode()));

        $article->setCleanedArticleText($outputFormatter->getFormattedText($article->getTopNode()));

        return $article;
    }

    private function getHTML($crawlCandidate, $parsingCandidate) {
        if (!empty($crawlCandidate->rawHTML)) {
            return $crawlCandidate->rawHTML;
        } else {
            $guzzle = new GuzzleClient();
            $response = $guzzle->get($parsingCandidate->url);

            return $response->getBody();
        }
    }

    private function getImageExtractor() {
        // TODO
    }

    private function getOutputFormatter() {
        return new OutputFormatter($this->config);
    }

    private function getDocCleaner() {
        return new StandardDocumentCleaner($this->config);
    }

    private function getDocument($url, $rawHtml) {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $doc = new DOMDocument(1.0);
        $doc->registerNodeClass('DOMElement', 'Goose\\DOM\\DOMElement');
        @$doc->loadHTML($rawHtml);

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        return $doc;
    }

    private function getExtractor() {
        return $this->config->getContentExtractor();
    }
}
