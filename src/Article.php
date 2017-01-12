<?php

namespace Goose;

use Goose\Images\Image;
use DOMWrap\Element;
use DOMWrap\Document;

/**
 * Article
 *
 * @package Goose
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Article {
    /**
     * Language of the article
     *
     * @var string
     */
    protected $language;

    /** @param string $language */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /** @return string */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * OpenGraph meta data
     *
     * @var string[]
     */
    protected $openGraph;

    /** @param string[] $openGraph */
    public function setOpenGraph($openGraph) {
        $this->openGraph = $openGraph;
    }

    /** @return string[] */
    public function getOpenGraph() {
        return $this->openGraph;
    }

    /**
     * Title of the article
     *
     * @var string
     */
    protected $title;

    /** @param string $title */
    public function setTitle($title) {
        $this->title = $title;
    }

    /** @return string */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Stores the lovely, pure text from the article, stripped of html, formatting, etc...
     * just raw text with paragraphs separated by newlines. This is probably what you want to use.
     *
     * @var string
     */
    protected $cleanedArticleText;

    /** @param string $cleanedArticleText */
    public function setCleanedArticleText($cleanedArticleText) {
        $this->cleanedArticleText = $cleanedArticleText;
    }

    /** @return string */
    public function getCleanedArticleText() {
        return $this->cleanedArticleText;
    }

    /**
     * Article with the originals HTML tags (<p>, <a>, ..)
     *
     * @var string
     */
    protected $htmlArticle;

    /** @param string $htmlArticle */
    public function setHtmlArticle($htmlArticle) {
        $this->htmlArticle = $htmlArticle;
    }

    /** @return string */
    public function getHtmlArticle() {
        return $this->htmlArticle;
    }

    /**
     * Meta description field in HTML source
     *
     * @var string
     */
    protected $metaDescription;

    /** @param string $metaDescription */
    public function setMetaDescription($metaDescription) {
        $this->metaDescription = $metaDescription;
    }

    /** @return string */
    public function getMetaDescription() {
        return $this->metaDescription;
    }

    /**
     * Meta keywords field in the HTML source
     *
     * @var string
     */
    protected $metaKeywords;

    /** @param string $metaKeywords */
    public function setMetaKeywords($metaKeywords) {
        $this->metaKeywords = $metaKeywords;
    }

    /** @return string */
    public function getMetaKeywords() {
        return $this->metaKeywords;
    }

    /**
     * The canonical link of this article if found in the meta data
     *
     * @var string
     */
    protected $canonicalLink;

    /** @param string $canonicalLink */
    public function setCanonicalLink($canonicalLink) {
        $this->canonicalLink = $canonicalLink;
    }

    /** @return string */
    public function getCanonicalLink() {
        return $this->canonicalLink;
    }

    /**
     * Domain of the article we're parsing
     *
     * @var string
     */
    protected $domain;

    /** @param string $domain */
    public function setDomain($domain) {
        $this->domain = $domain;
    }

    /** @return string */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Top Element we think is a candidate for the main body of the article
     *
     * @var Element|null
     */
    protected $topNode;

    /** @param Element|null $topNode */
    public function setTopNode(Element $topNode = null) {
        $this->topNode = $topNode;
    }

    /** @return Element|null */
    public function getTopNode() {
        return $this->topNode;
    }

    /**
     * Top Image object that we think represents this article
     *
     * @var Image|null
     */
    protected $topImage;

    /** @param Image|null $topImage */
    public function setTopImage(Image $topImage = null) {
        $this->topImage = $topImage;
    }

    /** @return Image|null */
    public function getTopImage() {
        return $this->topImage;
    }

    /**
     * All image candidates from article
     *
     * @var Image[]
     */
    protected $allImages = [];

    /** @param Image[] $allImages */
    public function setAllImages($allImages = []) {
        $this->allImages = $allImages;
    }

    /** @return Image[] */
    public function getAllImages() {
        return $this->allImages;
    }

    /**
     * Tags that may have been in the article, these are not meta keywords
     *
     * @var string[]
     */
    protected $tags = [];

    /** @param string[] $tags */
    public function setTags($tags) {
        $this->tags = $tags;
    }

    /** @return string[] */
    public function getTags() {
        return $this->tags;
    }

    /**
     *  List of links in the article
     *
     * @var string[]
     */
    protected $links = [];

    /** @param string[] $links */
    public function setLinks($links) {
        $this->links = $links;
    }

    /** @return string[] */
    public function getLinks() {
        return $this->links;
    }

    /**
     * List of any videos we found on the page like youtube, vimeo
     *
     * @var string[]
     */
    protected $videos = [];

    /** @param string[] $videos */
    public function setVideos($videos) {
        $this->videos = $videos;
    }

    /** @return string[] */
    public function getVideos() {
        return $this->videos;
    }

    /**
     * Final URL that we're going to try and fetch content against, this would be expanded if any
     * escaped fragments were found in the starting url
     *
     * @var string
     */
    protected $finalUrl;

    /** @param string $finalUrl */
    public function setFinalUrl($finalUrl) {
        $this->finalUrl = $finalUrl;
    }

    /** @return string */
    public function getFinalUrl() {
        return $this->finalUrl;
    }

    /**
     * MD5 hash of the url to use for various identification tasks
     *
     * @var string
     */
    protected $linkhash;

    /** @param string $linkhash */
    public function setLinkhash($linkhash) {
        $this->linkhash = $linkhash;
    }

    /** @return string */
    public function getLinkhash() {
        return $this->linkhash;
    }

    /**
     * Raw HTML straight from the network connection
     *
     * @var string
     */
    protected $rawHtml;

    /** @param string $rawHtml */
    public function setRawHtml($rawHtml) {
        $this->rawHtml = $rawHtml;
    }

    /** @return string */
    public function getRawHtml() {
        return $this->rawHtml;
    }

    /**
     * DOM Document object
     *
     * @var Document
     */
    protected $doc;

    /** @param Document $doc */
    public function setDoc(Document $doc) {
        $this->doc = $doc;
    }

    /** @return Document */
    public function getDoc() {
        return $this->doc;
    }

    /**
     * Original DOM document that contains a pure object from the original HTML without any cleaning
     * options done on it
     *
     * @var Document
     */
    protected $rawDoc;

    /** @param Document $rawDoc */
    public function setRawDoc(Document $rawDoc) {
        $this->rawDoc = $rawDoc;
    }

    /** @return Document */
    public function getRawDoc() {
        return $this->rawDoc;
    }

    /**
     * Original psr7 response object
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $rawResponse;

    /** @param \Psr\Http\Message\ResponseInterface|null $rawResponse */
    public function setRawResponse(\Psr\Http\Message\ResponseInterface $rawResponse) {
        $this->rawResponse = $rawResponse;
    }

    /** @return \Psr\Http\Message\ResponseInterface */
    public function getRawResponse() {
        return $this->rawResponse;
    }

    /**
     * Sometimes useful to try and know when the publish date of an article was
     *
     * @var \DateTime|null
     */
    protected $publishDate;

    /** @param \DateTime|null $publishDate */
    public function setPublishDate(\DateTime $publishDate = null) {
        $this->publishDate = $publishDate;
    }

    /** @return \DateTime|null */
    public function getPublishDate() {
        return $this->publishDate;
    }

    /**
     * A property bucket for consumers of goose to store custom data extractions.
     *
     * @return string
     */
    protected $additionalData;

    /** @param string $additionalData */
    public function setAdditionalData($additionalData) {
        $this->additionalData = $additionalData;
    }

    /** @return string */
    public function getAdditionalData() {
        return $this->additionalData;
    }

    /**
     * Facebook Open Graph data that that is found in Article Meta tags
     *
     * @return string
     */
    protected $openGraphData;

    /** @param string $openGraphData */
    public function setOpenGraphData($openGraphData) {
        $this->openGraphData = $openGraphData;
    }

    /** @return string */
    public function getOpenGraphData() {
        return $this->openGraphData;
    }

    /**
     * Most popular words used in the lovely article
     *
     * @return string[]
     */
    protected $popularWords = [];

    /** @param string[] $popularWords */
    public function setPopularWords($popularWords) {
        $this->popularWords = $popularWords;
    }

    /** @return string[] */
    public function getPopularWords() {
        return $this->popularWords;
    }
}