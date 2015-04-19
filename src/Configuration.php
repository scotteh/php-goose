<?php

namespace Goose;

use Goose\Extractors\StandardContentExtractor;
use Goose\Extractors\PublishDateExtractor;
use Goose\Extractors\AdditionalDataExtractor;
use Goose\Text\StopWords;

/**
 * Crawler
 *
 * @package Configuration
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Configuration {
    /**
     * @param mixed[] $options
     */
    public function __construct($options = []) {
        $this->contentExtractor = new StandardContentExtractor($this);
        $this->publishDateExtractor = new PublishDateExtractor($this);
        $this->additionalDataExtractor = new AdditionalDataExtractor($this);

        $this->initLanguage($this->language);

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }

    /**
     * @param string $language
     */
    public function initLanguage($language) {
        $this->stopWords = new StopWords($this, $language);
    }

    /** @var string */
    protected $language = 'en';

    /**
     * @param string $language
     */
    public function setLanguage($language) {
        $this->language = $language;

        $this->initLanguage($language);
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /** @var string */
    protected $localStoragePath = '/tmp/goose';

    /**
     * @param string $localStoragePath
     */
    public function setLocalStoragePath($localStoragePath) {
        $this->localStoragePath = $localStoragePath;
    }

    /**
     * @return string
     */
    public function getLocalStoragePath() {
        return $this->localStoragePath;
    }

    /** @var int */
    protected $minBytesForImages = 4500;

    /**
     * @param int $minBytesForImages
     */
    public function setMinBytesForImages($minBytesForImages) {
        $this->minBytesForImages = $minBytesForImages;
    }

    /**
     * @return int
     */
    public function getMinBytesForImages() {
        return $this->minBytesForImages;
    }

    /** @var int */
    protected $minWidth = 120;

    /**
     * @param int $minWidth
     */
    public function setMinWidth($minWidth) {
        $this->minWidth = $minWidth;
    }

    /**
     * @return int
     */
    public function getMinWidth() {
        return $this->minWidth;
    }

    /** @var int */
    protected $minHeight = 120;

    /**
     * @param int $minHeight
     */
    public function setMinHeight($minHeight) {
        $this->minHeight = $minHeight;
    }

    /**
     * @return int
     */
    public function getMinHeight() {
        return $this->minHeight;
    }

    /** @var bool */
    protected $enableImageFetching = true;

    /**
     * @param bool $enableImageFetching
     */
    public function setEnableImageFetching($enableImageFetching) {
        $this->enableImageFetching = $enableImageFetching;
    }

    /**
     * @return bool
     */
    public function getEnableImageFetching() {
        return $this->enableImageFetching;
    }

    /** @var bool */
    protected $enableAllImagesFetching = false;

    /**
     * @param bool $enableAllImagesFetching
     */
    public function setEnableAllImagesFetching($enableAllImagesFetching) {
        $this->enableAllImagesFetching = $enableAllImagesFetching;
    }

    /**
     * @return bool
     */
    public function getEnableAllImagesFetching() {
        return $this->enableAllImagesFetching;
    }

    /** @var int */
    protected $timeout = 10000;

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /** @var string */
    protected $browserUserAgent = 'Mozilla/5.0 (X11; U; Linux x86_64; de; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8';

    /**
     * @param string $browserUserAgent
     */
    public function setBrowserUserAgent($browserUserAgent) {
        $this->browserUserAgent = $browserUserAgent;
    }

    /**
     * @return string
     */
    public function getBrowserUserAgent() {
        return $this->browserUserAgent;
    }

    /** @var string */
    protected $browserReferer = 'https://www.google.com';

    /**
     * @param string $browserReferer
     */
    public function setBrowserReferer($browserReferer) {
        $this->browserReferer = $browserReferer;
    }

    /**
     * @return string
     */
    public function getBrowserReferer() {
        return $this->browserReferer;
    }

    /** @var \Goose\Extractors\ContentExtractor */
    protected $contentExtractor;

    /**
     * @param \Goose\Extractors\ContentExtractor $contentExtractor
     */
    public function setContentExtractor($contentExtractor) {
        if (is_null($contentExtractor) || !is_object($contentExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->contentExtractor = $contentExtractor;
    }

    /**
     * @return \Goose\Extractors\ContentExtractor
     */
    public function getContentExtractor() {
        return $this->contentExtractor;
    }

    /** @var \Goose\Extractors\PublishDateExtractor */
    protected $publishDateExtractor;

    /**
     * @param \Goose\Extractors\PublishDateExtractor $publishDateExtractor
     */
    public function setPublishDateExtractor($publishDateExtractor) {
        if (is_null($publishDateExtractor) || !is_object($publishDateExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->publishDateExtractor = $publishDateExtractor;
    }

    /**
     * @return \Goose\Extractors\PublishDateExtractor
     */
    public function getPublishDateExtractor() {
        return $this->publishDateExtractor;
    }

    /** @var \Goose\Extractors\AdditionalDataExtractor */
    protected $additionalDataExtractor;

    /**
     * @param \Goose\Extractors\AdditionalDataExtractor $additionalDataExtractor
     */
    public function setAdditionalDataExtractor($additionalDataExtractor) {
        if (is_null($additionalDataExtractor) || !is_object($additionalDataExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->additionalDataExtractor = $additionalDataExtractor;
    }

    /**
     * @return \Goose\Extractors\AdditionalDataExtractor
     */
    public function getAdditionalDataExtractor() {
        return $this->additionalDataExtractor;
    }

    /** @var \Goose\Extractors\OpenGraphDataExtractor */
    protected $openGraphDataExtractor;

    /**
     * @param \Goose\Extractors\OpenGraphDataExtractor $openGraphDataExtractor
     */
    public function setOpenGraphDataExtractor($openGraphDataExtractor) {
        if (is_null($openGraphDataExtractor) || !is_object($openGraphDataExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->openGraphDataExtractor = $openGraphDataExtractor;
    }

    /**
     * @return \Goose\Extractors\OpenGraphDataExtractor
     */
    public function getOpenGraphDataExtractor() {
        return $this->openGraphDataExtractor;
    }

    /** @var \Goose\Text\StopWords */
    protected $stopWords;

    /**
     * @param \Goose\Text\StopWords $stopWords
     */
    public function setStopWords($stopWords) {
        if (is_null($stopWords) || !is_object($stopWords)) {
            throw new InvalidArgumentException('Stop words must be a valid object!');
        }

        $this->stopWords = $stopWords;
    }

    /**
     * @return \Goose\Text\StopWords
     */
    public function getStopWords() {
        return $this->stopWords;
    }

    /** @var mixed[] */
    protected $guzzle;

    /**
     * @param mixed[] $guzzle
     */
    public function setGuzzle($guzzle) {
        $this->guzzle = $guzzle;
    }

    /**
     * @return mixed[]
     */
    public function getGuzzle() {
        return $this->guzzle;
    }
}