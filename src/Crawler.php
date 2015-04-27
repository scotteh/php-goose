<?php

namespace Goose;

use GuzzleHttp\Client as GuzzleClient;
use Goose\Utils\URLHelper;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;
use Goose\Images\Image;
use Goose\Images\StandardImageExtractor;
use Goose\Extractors\ExtractorInterface;
use Goose\Cleaners\StandardDocumentCleaner;
use Goose\OutputFormatters\StandardOutputFormatter;

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

        $doc = $this->getDocument($rawHTML);

        $documentCleaner = $this->getDocumentCleaner();
        $outputFormatter = $this->getOutputFormatter();
        $contentExtractor = $this->getContentExtractor();
        $publishDateExtractor = $this->config->getPublishDateExtractor();
        $additionalDataExtractor = $this->config->getAdditionalDataExtractor();

        $article->setFinalUrl($parseCandidate->url);
        $article->setDomain($parseCandidate->parts->host);
        $article->setLinkhash($parseCandidate->linkhash);
        $article->setRawHtml($rawHTML);
        $article->setDoc($doc);
        $article->setRawDoc(clone $doc);

        $language = $contentExtractor->getMetaLanguage($article);

        $this->config->setLanguage($language);

        $article->setLanguage($language);
        $article->setTitle($contentExtractor->getTitle($article));
        $article->setMetaDescription($contentExtractor->getMetaDescription($article));
        $article->setMetaKeywords($contentExtractor->getMetaKeywords($article));
        $article->setCanonicalLink($contentExtractor->getCanonicalLink($article));
        $article->setTags($contentExtractor->extractTags($article));

        if ($publishDateExtractor instanceof ExtractorInterface) {
            $article->setPublishDate($publishDateExtractor->extract($article));
        }

        if ($additionalDataExtractor instanceof ExtractorInterface) {
            $article->setAdditionalData($additionalDataExtractor->extract($article));
        }

        $documentCleaner->clean($article);

        $topNode = $contentExtractor->getBestNode($article);

        if ($topNode instanceof DOMElement) {
            $article->setTopNode($topNode);

            $article->setMovies($contentExtractor->extractVideos($article->getTopNode()));
            $article->setLinks($contentExtractor->extractLinks($article->getTopNode()));

            if ($this->config->getEnableImageFetching()) {
                $imageExtractor = $this->getImageExtractor();

                $bestImage = $imageExtractor->getBestImage($article);

                if ($bestImage instanceof Image) {
                    $article->setTopImage($bestImage);
                }

                if ($this->config->getEnableAllImagesFetching()) {
                    $article->setAllImages($imageExtractor->getAllImages($article));
                }
            }

            $contentExtractor->postExtractionCleanup($article->getTopNode());

            $article->setCleanedArticleText($outputFormatter->getFormattedText($article->getTopNode()));
            $article->setHtmlArticle($outputFormatter->cleanupHtml($article->getTopNode()));
        }

        $article->setPopularWords($contentExtractor->getPopularWords($article));

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
        return new StandardImageExtractor($this->config);
    }

    /**
     * @return OutputFormatter
     */
    private function getOutputFormatter() {
        return new StandardOutputFormatter($this->config);
    }

    /**
     * @return DocumentCleaner
     */
    private function getDocumentCleaner() {
        return new StandardDocumentCleaner($this->config);
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
}

