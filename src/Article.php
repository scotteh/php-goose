<?php declare(strict_types=1);

namespace Goose;

use Goose\Images\Image;
use DOMWrap\{Element, Document};

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
    protected $language;

    /**
     * @param string $language
     *
     * @return self
     */
    public function setLanguage(string $language = null): self {
        $this->language = $language;

        return $this;
    }

    /** @return string|null */
    public function getLanguage(): ?string {
        return $this->language;
    }

    /**
     * OpenGraph meta data
     *
     * @var string[]
     */
    protected $openGraph = [];

    /**
     * @param string[] $openGraph
     *
     * @return self
     */
    public function setOpenGraph(array $openGraph): self {
        $this->openGraph = $openGraph;

        return $this;
    }

    /** @return string[] */
    public function getOpenGraph(): array {
        return $this->openGraph;
    }

    /**
     * Title of the article
     *
     * @var string|null
     */
    protected $title;

    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title = null): self {
        $this->title = $title;

        return $this;
    }

    /** @return string|null */
    public function getTitle(): ?string {
        return $this->title;
    }

    /**
     * Stores the lovely, pure text from the article, stripped of html, formatting, etc...
     * just raw text with paragraphs separated by newlines. This is probably what you want to use.
     *
     * @var string|null
     */
    protected $cleanedArticleText;

    /**
     * @param string $cleanedArticleText
     *
     * @return self
     */
    public function setCleanedArticleText(string $cleanedArticleText = null): self {
        $this->cleanedArticleText = $cleanedArticleText;

        return $this;
    }

    /** @return string|null */
    public function getCleanedArticleText(): ?string {
        return $this->cleanedArticleText;
    }

    /**
     * Article with the originals HTML tags (<p>, <a>, ..)
     *
     * @var string|null
     */
    protected $htmlArticle;

    /**
     * @param string $htmlArticle
     *
     * @return self
     */
    public function setHtmlArticle(string $htmlArticle = null): self {
        $this->htmlArticle = $htmlArticle;

        return $this;
    }

    /** @return string|null */
    public function getHtmlArticle(): ?string {
        return $this->htmlArticle;
    }

    /**
     * Meta description field in HTML source
     *
     * @var string|null
     */
    protected $metaDescription;

    /**
     * @param string $metaDescription
     *
     * @return self
     */
    public function setMetaDescription(string $metaDescription = null): self {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /** @return string|null */
    public function getMetaDescription(): ?string {
        return $this->metaDescription;
    }

    /**
     * Meta keywords field in the HTML source
     *
     * @var string|null
     */
    protected $metaKeywords;

    /**
     * @param string $metaKeywords
     *
     * @return self
     */
    public function setMetaKeywords(string $metaKeywords = null): self {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    /** @return string */
    public function getMetaKeywords(): ?string {
        return $this->metaKeywords;
    }

    /**
     * The canonical link of this article if found in the meta data
     *
     * @var string|null
     */
    protected $canonicalLink;

    /**
     * @param string $canonicalLink
     *
     * @return self
     */
    public function setCanonicalLink(string $canonicalLink = null): self {
        $this->canonicalLink = $canonicalLink;

        return $this;
    }

    /** @return string|null */
    public function getCanonicalLink(): ?string {
        return $this->canonicalLink;
    }

    /**
     * Domain of the article we're parsing
     *
     * @var string|null
     */
    protected $domain;

    /**
     * @param string $domain
     *
     * @return self
     */
    public function setDomain(string $domain = null): self {
        $this->domain = $domain;

        return $this;
    }

    /** @return string|null */
    public function getDomain(): ?string {
        return $this->domain;
    }

    /**
     * Top Element we think is a candidate for the main body of the article
     *
     * @var Element|null
     */
    protected $topNode;

    /**
     * @param Element|null $topNode
     *
     * @return self
     */
    public function setTopNode(Element $topNode = null): self {
        $this->topNode = $topNode;

        return $this;
    }

    /** @return Element|null */
    public function getTopNode(): ?Element {
        return $this->topNode;
    }

    /**
     * Top Image object that we think represents this article
     *
     * @var Image|null
     */
    protected $topImage;

    /**
     * @param Image|null $topImage
     *
     * @return self
     */
    public function setTopImage(Image $topImage = null): self {
        $this->topImage = $topImage;

        return $this;
    }

    /** @return Image|null */
    public function getTopImage(): ?Image {
        return $this->topImage;
    }

    /**
     * All image candidates from article
     *
     * @var Image[]
     */
    protected $allImages = [];

    /**
     * @param Image[] $allImages
     *
     * @return self
     */
    public function setAllImages(array $allImages = []): self {
        $this->allImages = $allImages;

        return $this;
    }

    /** @return Image[] */
    public function getAllImages(): array {
        return $this->allImages;
    }

    /**
     * Tags that may have been in the article, these are not meta keywords
     *
     * @var string[]
     */
    protected $tags = [];

    /**
     * @param string[] $tags
     *
     * @return self
     */
    public function setTags(array $tags): self {
        $this->tags = $tags;

        return $this;
    }

    /** @return string[] */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     *  List of links in the article
     *
     * @var string[]
     */
    protected $links = [];

    /**
     * @param string[] $links
     *
     * @return self
     */
    public function setLinks(array $links): self {
        $this->links = $links;

        return $this;
    }

    /** @return string[] */
    public function getLinks(): array {
        return $this->links;
    }

    /**
     * List of any videos we found on the page like youtube, vimeo
     *
     * @var string[]
     */
    protected $videos = [];

    /**
     * @param string[] $videos
     *
     * @return self
     */
    public function setVideos(array $videos): self {
        $this->videos = $videos;

        return $this;
    }

    /** @return string[] */
    public function getVideos(): array {
        return $this->videos;
    }

    /**
     * Final URL that we're going to try and fetch content against, this would be expanded if any
     * escaped fragments were found in the starting url
     *
     * @var string|null
     */
    protected $finalUrl;

    /**
     * @param string $finalUrl
     *
     * @return self
     */
    public function setFinalUrl(string $finalUrl = null): self {
        $this->finalUrl = $finalUrl;

        return $this;
    }

    /** @return string|null */
    public function getFinalUrl(): ?string {
        return $this->finalUrl;
    }

    /**
     * MD5 hash of the url to use for various identification tasks
     *
     * @var string|null
     */
    protected $linkhash;

    /**
     * @param string $linkhash
     *
     * @return self
     */
    public function setLinkhash(string $linkhash = null): self {
        $this->linkhash = $linkhash;

        return $this;
    }

    /** @return string */
    public function getLinkhash(): ?string {
        return $this->linkhash;
    }

    /**
     * Raw HTML straight from the network connection
     *
     * @var string|null
     */
    protected $rawHtml;

    /**
     * @param string $rawHtml
     *
     * @return self
     */
    public function setRawHtml(string $rawHtml = null): self {
        $this->rawHtml = $rawHtml;

        return $this;
    }

    /** @return string|null */
    public function getRawHtml(): ?string {
        return $this->rawHtml;
    }

    /**
     * DOM Document object
     *
     * @var Document
     */
    protected $doc;

    /**
     * @param Document $doc
     *
     * @return self
     */
    public function setDoc(Document $doc): self {
        $this->doc = $doc;

        return $this;
    }

    /** @return Document */
    public function getDoc(): Document {
        return $this->doc;
    }

    /**
     * Original DOM document that contains a pure object from the original HTML without any cleaning
     * options done on it
     *
     * @var Document
     */
    protected $rawDoc;

    /**
     * @param Document $rawDoc
     *
     * @return self
     */
    public function setRawDoc(Document $rawDoc): self {
        $this->rawDoc = $rawDoc;

        return $this;
    }

    /** @return Document */
    public function getRawDoc(): Document {
        return $this->rawDoc;
    }

    /**
     * Original psr7 response object
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $rawResponse;

    /**
     * @param \Psr\Http\Message\ResponseInterface|null $rawResponse
     *
     * @return self
     */
    public function setRawResponse(\Psr\Http\Message\ResponseInterface $rawResponse): self {
        $this->rawResponse = $rawResponse;

        return $this;
    }

    /** @return \Psr\Http\Message\ResponseInterface */
    public function getRawResponse(): \Psr\Http\Message\ResponseInterface {
        return $this->rawResponse;
    }

    /**
     * Sometimes useful to try and know when the publish date of an article was
     *
     * @var \DateTime|null
     */
    protected $publishDate;

    /**
     * @param \DateTime|null $publishDate
     *
     * @return self
     */
    public function setPublishDate(\DateTime $publishDate = null): self {
        $this->publishDate = $publishDate;

        return $this;
    }

    /** @return \DateTime|null */
    public function getPublishDate(): ?\DateTime {
        return $this->publishDate;
    }

    /**
     * A property bucket for consumers of goose to store custom data extractions.
     *
     * @var string|null
     */
    protected $additionalData;

    /**
     * @param string|null $additionalData
     *
     * @return self
     */
    public function setAdditionalData(string $additionalData = null): self {
        $this->additionalData = $additionalData;

        return $this;
    }

    /** @return string|null */
    public function getAdditionalData(): ?string {
        return $this->additionalData;
    }

    /**
     * Facebook Open Graph data that that is found in Article Meta tags
     *
     * @var string|null
     */
    protected $openGraphData;

    /**
     * @param string $openGraphData
     *
     * @return self
     */
    public function setOpenGraphData(string $openGraphData = null): self {
        $this->openGraphData = $openGraphData;

        return $this;
    }

    /** @return string|null */
    public function getOpenGraphData(): ?string {
        return $this->openGraphData;
    }

    /**
     * Most popular words used in the lovely article
     *
     * @var string[]
     */
    protected $popularWords = [];

    /**
     * @param string[] $popularWords
     *
     * @return self
     */
    public function setPopularWords(array $popularWords): self {
        $this->popularWords = $popularWords;

        return $this;
    }

    /** @return string[] */
    public function getPopularWords(): array {
        return $this->popularWords;
    }
}