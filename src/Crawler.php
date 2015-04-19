<?php

namespace Goose;

use GuzzleHttp\Client as GuzzleClient;
use Goose\Utils\URLHelper;
use Goose\DOM\DOMDocument;
use Goose\Images\StandardImageExtractor;
use Goose\Extractors\ExtractorInterface;
use Goose\Cleaners\StandardDocumentCleaner;
use Goose\OutputFormatters\StandardOutputFormatter;

class Crawler {
    protected $config;

    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    public function crawl($crawlCandidate) {
        $article = new Article();

        $parseCandidate = URLHelper::getCleanedUrl($crawlCandidate->url);
        $rawHtml = $this->getHTML($crawlCandidate, $parseCandidate);
        $doc = $this->getDocument($rawHtml);

        $extractor = $this->getExtractor();
        $documentCleaner = $this->getDocumentCleaner();
        $outputFormatter = $this->getOutputFormatter();
        $publishDateExtractor = $this->config->getPublishDateExtractor();
        $additionalDataExtractor = $this->config->getAdditionalDataExtractor();

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
        $article->setMetaDescription($extractor->getMetaDescription($article));
        $article->setMetaKeywords($extractor->getMetaKeywords($article));
        $article->setCanonicalLink($extractor->getCanonicalLink($article));
        $article->setTags($extractor->extractTags($article));

        if ($publishDateExtractor instanceof ExtractorInterface) {
            $article->setPublishDate($publishDateExtractor->extract($article));
        }

        if ($additionalDataExtractor instanceof ExtractorInterface) {
            $article->setAdditionalData($additionalDataExtractor->extract($article));
        }

        $documentCleaner->clean($article);

        $article->setTopNode($extractor->calculateBestNodeBasedOnClustering($article));

        $txt = $article->getTitle() . $article->getMetaDescription();

        if ($article->getTopNode()) {
            $article->setMovies($extractor->extractVideos($article->getTopNode()));
            $article->setLinks($extractor->extractLinks($article->getTopNode()));

            if ($this->config->getEnableImageFetching()) {
                $imageExtractor = $this->getImageExtractor();

                $article->setTopImage($imageExtractor->getBestImage($article));

                if ($this->config->getEnableAllImagesFetching()) {
                    $article->setAllImages($imageExtractor->getAllImages($article));
                }
            }

            $article->setTopNode($extractor->postExtractionCleanup($article->getTopNode()));
            $article->setCleanedArticleText($outputFormatter->getFormattedText($article->getTopNode()));
            $article->setHtmlArticle($outputFormatter->cleanupHtml($article->getTopNode()));

            $txt .= $article->getCleanedArticleText();
        }

        $article->setPopularWords($extractor->getPopularWords($txt));

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

    private function getDocumentCleaner() {
        return new StandardDocumentCleaner($this->config);
    }

    private function getDocument($rawHtml) {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $fn = function($matches) {
            return (
                isset($matches[1])
                ? '</script> -->'
                : '<!-- <script>'
            );
        };

        $rawHtml = preg_replace_callback('@<([/])?script[^>]*>@i', $fn, $rawHtml);

        if (mb_detect_encoding($rawHtml, mb_detect_order(), true) === 'UTF-8') {
            $rawHtml = mb_convert_encoding($rawHtml, 'HTML-ENTITIES', 'UTF-8');
        }

        $doc = new DOMDocument(1.0);
        $doc->registerNodeClass('DOMElement', 'Goose\\DOM\\DOMElement');
        $doc->loadHTML($rawHtml);

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        return $doc;
    }

    private function getExtractor() {
        return $this->config->getContentExtractor();
    }
}

