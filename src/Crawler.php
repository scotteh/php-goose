<?php

namespace Goose;

use GuzzleHttp\Client as GuzzleClient;
use Goose\Utils\Debug;
use Goose\Utils\URLHelper;
use Goose\DOM\DOMDocument;
use Goose\Images\StandardImageExtractor;
use Goose\Cleaners\StandardDocumentCleaner;
use Goose\OutputFormatters\StandardOutputFormatter;

class Crawler {
    protected $config;

    public function __construct($config = []) {
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

        $language = $extractor->getMetaLanguage($article);

        $this->config->setLanguage($language);

        $article->setLanguage($language);
        $article->setTitle($extractor->getTitle($article));
        $article->setPublishDate($this->config->getPublishDateExtractor($doc));
        $article->setAdditionalData($this->config->getAdditionalDataExtractor($doc));
        $article->setMetaDescription($extractor->getMetaDescription($article));
        $article->setMetaKeywords($extractor->getMetaKeywords($article));
        $article->setCanonicalLink($extractor->getCanonicalLink($article));
        $article->setTags($extractor->extractTags($article));
        $article->setDoc($docCleaner->clean($article));

        /*if (!$article->getPublishDate()) {
            $article->setPublishDate($extractor->getDateFromURL($article->getCanonicalLink()));
        }*/

        $article->setTopNode($extractor->calculateBestNodeBasedOnClustering($article));

        if ($article->getTopNode()) {
            $article->setMovies($extractor->extractVideos($article->getTopNode()));
            $article->setLinks($extractor->extractLinks($article->getTopNode()));

            if ($this->config->getEnableImageFetching()) {
                $imageExtractor = $this->getImageExtractor();

                $article->setTopImage($imageExtractor->getBestImage($article));

                if ($this->config->getEnableAllImagesFetching()) {
                    $article->setAllImages($imageExtractor->getAllImages($article->getTopNode()));
                }
            }

            $article->setTopNode($extractor->postExtractionCleanup($article->getTopNode()));
            $article->setCleanedArticleText($outputFormatter->getFormattedText($article->getTopNode()));
            $article->setHtmlArticle($outputFormatter->cleanupHtml($article->getTopNode()));
        }

        return $article;
    }

    private function getHTML($crawlCandidate, $parsingCandidate) {
        if (!empty($crawlCandidate->rawHTML)) {
            return $crawlCandidate->rawHTML;
        } else {
            $config = $this->config->getGuzzle();

            if (!is_array($config)) {
                $config = [];
            }

            $guzzle = new GuzzleClient();
            $response = $guzzle->get($parsingCandidate->url, $config);

            return $response->getBody();
        }
    }

    private function getImageExtractor() {
        return new StandardImageExtractor($this->config);
    }

    private function getOutputFormatter() {
        return new StandardOutputFormatter($this->config);
    }

    private function getDocCleaner() {
        return new StandardDocumentCleaner($this->config);
    }

    private function getDocument($url, $rawHtml) {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $fn = function($matches){
            return (
                isset($matches[1])
                ? '</script> -->'
                : '<!-- <script>'
            );
        };

        $rawHtml = preg_replace_callback('@<([/])?script[^>]*>@i', $fn, $rawHtml);

        if (mb_detect_encoding($rawHtml, mb_detect_order(), true) === 'UTF-8') {
            $rawHtml = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function($match) {
                list($utf8) = $match;
                return mb_convert_encoding($utf8, 'HTML-ENTITIES', 'UTF-8');
            }, $rawHtml);
        }

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
