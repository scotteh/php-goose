<?php

namespace Goose;

use Goose\Images\Image;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;
use Goose\Extractors\ExtractorInterface;

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
     * @var string|null
     */
    protected $language = null;

    /** @param string|null $language */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /** @return string|null */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * Title of the article
     *
     * @var string|null
     */
    protected $title = null;

    /** @param string|null $title */
    public function setTitle($title) {
        $this->title = $title;
    }

    /** @return string|null */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Stores the lovely, pure text from the article, stripped of html, formatting, etc...
     * just raw text with paragraphs separated by newlines. This is probably what you want to use.
     *
     * @var string|null
     */
    protected $cleanedArticleText = null;

    /** @param string|null $cleanedArticleText */
    public function setCleanedArticleText($cleanedArticleText) {
        $this->cleanedArticleText = $cleanedArticleText;
    }

    /** @return string|null */
    public function getCleanedArticleText() {
        return $this->cleanedArticleText;
    }

    /**
     * Article with the originals HTML tags (<p>, <a>, ..)
     *
     * @var string|null
     */
    protected $htmlArticle = null;

    /** @param string|null $htmlArticle */
    public function setHtmlArticle($htmlArticle) {
        $this->htmlArticle = $htmlArticle;
    }

    /** @return string|null */
    public function getHtmlArticle() {
        return $this->htmlArticle;
    }

    /**
     * Meta description field in HTML source
     *
     * @var string|null
     */
    protected $metaDescription = null;

    /** @param string|null $metaDescription */
    public function setMetaDescription($metaDescription) {
        $this->metaDescription = $metaDescription;
    }

    /** @return string|null */
    public function getMetaDescription() {
        return $this->metaDescription;
    }

    /**
     * Meta keywords field in the HTML source
     *
     * @var string|null
     */
    protected $metaKeywords = null;

    /** @param string|null $metaKeywords */
    public function setMetaKeywords($metaKeywords) {
        $this->metaKeywords = $metaKeywords;
    }

    /** @return string|null */
    public function getMetaKeywords() {
        return $this->metaKeywords;
    }

    /**
     * The canonical link of this article if found in the meta data
     *
     * @var string|null
     */
    protected $canonicalLink = null;

    /** @param string|null $canonicalLink */
    public function setCanonicalLink($canonicalLink) {
        $this->canonicalLink = $canonicalLink;
    }

    /** @return string|null */
    public function getCanonicalLink() {
        return $this->canonicalLink;
    }

    /**
     * Domain of the article we're parsing
     *
     * @var string|null
     */
    protected $domain = null;

    /** @param string|null $domain */
    public function setDomain($domain) {
        $this->domain = $domain;
    }

    /** @return string|null */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Top Element we think is a candidate for the main body of the article
     *
     * @var DOMElement|null
     */
    protected $topNode = null;

    /** @param DOMElement|null $topNode */
    public function setTopNode(DOMElement $topNode = null) {
        $this->topNode = $topNode;
    }

    /** @return DOMElement|null */
    public function getTopNode() {
        return $this->topNode;
    }

    /**
     * Top Image object that we think represents this article
     *
     * @var Image|null
     */
    protected $topImage = null;

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
    public function setAllImages(Image $allImages = null) {
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
     * List of any movies we found on the page like youtube, vimeo
     *
     * @var string[]
     */
    protected $movies = [];

    /** @param string[] $movies */
    public function setMovies($movies) {
        $this->movies = $movies;
    }

    /** @return string[] */
    public function getMovies() {
        return $this->movies;
    }

    /**
     * Final URL that we're going to try and fetch content against, this would be expanded if any
     * escaped fragments were found in the starting url
     *
     * @var string|null
     */
    protected $finalUrl = null;

    /** @param string|null $finalUrl */
    public function setFinalUrl($finalUrl) {
        $this->finalUrl = $finalUrl;
    }

    /** @return string|null */
    public function getFinalUrl() {
        return $this->finalUrl;
    }

    /**
     * MD5 hash of the url to use for various identification tasks
     *
     * @var string|null
     */
    protected $linkhash = null;

    /** @param string|null $linkhash */
    public function setLinkhash($linkhash) {
        $this->linkhash = $linkhash;
    }

    /** @return string|null */
    public function getLinkhash() {
        return $this->linkhash;
    }

    /**
     * Raw HTML straight from the network connection
     *
     * @var string|null
     */
    protected $rawHtml = null;

    /** @param string|null $rawHtml */
    public function setRawHtml($rawHtml) {
        $this->rawHtml = $rawHtml;
    }

    /** @return string|null */
    public function getRawHtml() {
        return $this->rawHtml;
    }

    /**
     * DOM Document object
     *
     * @var DOMDocument|null
     */
    protected $doc = null;

    /** @param DOMDocument|null $doc */
    public function setDoc(DOMDocument $doc = null) {
        $this->doc = $doc;
    }

    /** @return DOMDocument|null */
    public function getDoc() {
        return $this->doc;
    }

    /**
     * Original DOM document that contains a pure object from the original HTML without any cleaning
     * options done on it
     *
     * @var DOMDocument|null
     */
    protected $rawDoc = null;

    /** @param DOMDocument|null $rawDoc */
    public function setRawDoc(DOMDocument $rawDoc = null) {
        $this->rawDoc = $rawDoc;
    }

    /** @return DOMDocument|null */
    public function getRawDoc() {
        return $this->rawDoc;
    }

    /**
     * Sometimes useful to try and know when the publish date of an article was
     *
     * @var string|null
     */
    protected $publishDate = null;

    /** @param string|null $publishDate */
    public function setPublishDate($publishDate) {
        $this->publishDate = $publishDate;
    }

    /** @return string|null */
    public function getPublishDate() {
        return $this->publishDate;
    }

    /**
     * A property bucket for consumers of goose to store custom data extractions.
     *
     * @return ExtractorInterface|null
     */
    protected $additionalData = null;

    /** @param ExtractorInterface|null $additionalData */
    public function setAdditionalData(ExtractorInterface $additionalData = null) {
        $this->additionalData = $additionalData;
    }

    /** @return ExtractorInterface|null */
    public function getAdditionalData() {
        return $this->additionalData;
    }

    /**
     * Facebook Open Graph data that that is found in Article Meta tags
     *
     * @return ExtractorInterface|null
     */
    protected $openGraphData = null;

    /** @param ExtractorInterface|null $openGraphData */
    public function setOpenGraphData(ExtractorInterface $openGraphData = null) {
        $this->openGraphData = $openGraphData;
    }

    /** @return ExtractorInterface|null */
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