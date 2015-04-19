<?php

namespace Goose;

class Article {
    /**
     * language of the article
     */
    protected $language;

    public function setLanguage($language) {
        $this->language = $language;
    }

    public function getLanguage() {
        return $this->language;
    }

    /**
     * title of the article
     */
    protected $title;

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    /**
     * stores the lovely, pure text from the article, stripped of html, formatting, etc...
     * just raw text with paragraphs separated by newlines. This is probably what you want to use.
     */
    protected $cleanedArticleText;

    public function setCleanedArticleText($cleanedArticleText) {
        $this->cleanedArticleText = $cleanedArticleText;
    }

    public function getCleanedArticleText() {
        return $this->cleanedArticleText;
    }

    /**
     * article with the originals HTML tags (<p>, <a>, ..)
     */
    protected $htmlArticle;

    public function setHtmlArticle($htmlArticle) {
        $this->htmlArticle = $htmlArticle;
    }

    public function getHtmlArticle() {
        return $this->htmlArticle;
    }

    /**
     * meta description field in HTML source
     */
    protected $metaDescription;

    public function setMetaDescription($metaDescription) {
        $this->metaDescription = $metaDescription;
    }

    public function getMetaDescription() {
        return $this->metaDescription;
    }

    /**
     * meta keywords field in the HTML source
     */
    protected $metaKeywords;

    public function setMetaKeywords($metaKeywords) {
        $this->metaKeywords = $metaKeywords;
    }

    public function getMetaKeywords() {
        return $this->metaKeywords;
    }

    /**
     * The canonical link of this article if found in the meta data
     */
    protected $canonicalLink;

    public function setCanonicalLink($canonicalLink) {
        $this->canonicalLink = $canonicalLink;
    }

    public function getCanonicalLink() {
        return $this->canonicalLink;
    }

    /**
     * holds the domain of this article we're parsing
     */
    protected $domain;

    public function setDomain($domain) {
        $this->domain = $domain;
    }

    public function getDomain() {
        return $this->domain;
    }

    /**
     * holds the top Element we think is a candidate for the main body of the article
     */
    protected $topNode;

    public function setTopNode($topNode) {
        $this->topNode = $topNode;
    }

    public function getTopNode() {
        return $this->topNode;
    }

    /**
     * holds the top Image object that we think represents this article
     */
    protected $topImage;

    public function setTopImage($topImage) {
        $this->topImage = $topImage;
    }

    public function getTopImage() {
        return $this->topImage;
    }

    /**
     * holds all cadidate images from article
     */
    protected $allImages;

    public function setAllImages($allImages) {
        $this->allImages = $allImages;
    }

    public function getAllImages() {
        return $this->allImages;
    }

    /**
     * holds a set of tags that may have been in the artcle, these are not meta keywords
     */
    protected $tags;

    public function setTags($tags) {
        $this->tags = $tags;
    }

    public function getTags() {
        return $this->tags;
    }

    /**
     *  holds a list of links in the article
     */
    protected $links;

    public function setLinks($links) {
        $this->links = $links;
    }

    public function getLinks() {
        return $this->links;
    }

    /**
     * holds a list of any movies we found on the page like youtube, vimeo
     */
    protected $movies;

    public function setMovies($movies) {
        $this->movies = $movies;
    }

    public function getMovies() {
        return $this->movies;
    }

    /**
     * stores the final URL that we're going to try and fetch content against, this would be expanded if any
     * escaped fragments were found in the starting url
     */
    protected $finalUrl;

    public function setFinalUrl($finalUrl) {
        $this->finalUrl = $finalUrl;
    }

    public function getFinalUrl() {
        return $this->finalUrl;
    }

    /**
     * stores the MD5 hash of the url to use for various identification tasks
     */
    protected $linkhash;

    public function setLinkhash($linkhash) {
        $this->linkhash = $linkhash;
    }

    public function getLinkhash() {
        return $this->linkhash;
    }

    /**
     * stores the RAW HTML straight from the network connection
     */
    protected $rawHtml;

    public function setRawHtml($rawHtml) {
        $this->rawHtml = $rawHtml;
    }

    public function getRawHtml() {
        return $this->rawHtml;
    }

    /**
     * the DOM Document object
     */
    protected $doc;

    public function setDoc($doc) {
        $this->doc = $doc;
    }

    public function getDoc() {
        return $this->doc;
    }

    /**
     * this is the original DOM document that contains a pure object from the original HTML without any cleaning
     * options done on it
     */
    protected $rawDoc;

    public function setRawDoc($rawDoc) {
        $this->rawDoc = $rawDoc;
    }

    public function getRawDoc() {
        return $this->rawDoc;
    }

    /**
     * Sometimes useful to try and know when the publish date of an article was
     */
    protected $publishDate;

    public function setPublishDate($publishDate) {
        $this->publishDate = $publishDate;
    }

    public function getPublishDate() {
        return $this->publishDate;
    }

    /**
     * A property bucket for consumers of goose to store custom data extractions.
     * @return object
     */
    protected $additionalData;

    public function setAdditionalData($additionalData) {
        $this->additionalData = $additionalData;
    }

    public function getAdditionalData() {
        return $this->additionalData;
    }

    /**
     * Facebook Open Graph data that that is found in Article Meta tags
     */
    protected $openGraphData;

    public function setOpenGraphData($openGraphData) {
        $this->openGraphData = $openGraphData;
    }

    public function getOpenGraphData() {
        return $this->openGraphData;
    }

    /**
     * most popular words used in the lovely article content
     */
    protected $popularWords;

    public function setPopularWords($popularWords) {
        $this->popularWords = $popularWords;
    }

    public function getPopularWords() {
        return $this->popularWords;
    }
}