<?php

namespace Goose;

use Goose\Extractors\StandardContentExtractor;
use Goose\Extractors\PublishDateExtractor;
use Goose\Extractors\AdditionalDataExtractor;
use Goose\Text\StopWords;

class Configuration {
    public function __construct($options = array()) {
        $this->contentExtractor = new StandardContentExtractor($this);
        $this->publishDateExtractor = new PublishDateExtractor($this);
        $this->additionalDataExtractor = new AdditionalDataExtractor($this);

        $this->stopWords = new StopWords($this, 'en');

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), $value);
            }
        }
    }

    protected $localStoragePath = '/tmp/goose';

    public function setLocalStoragePath(string $localStoragePath) {
        $this->localStoragePath = $localStoragePath;
    }

    public function getLocalStoragePath() {
        return $this->localStoragePath;
    }

    protected $minBytesForImages = 4500;

    public function setMinBytesForImages(int $minBytesForImages) {
        $this->minBytesForImages = $minBytesForImages;
    }

    public function getMinBytesForImages() {
        return $this->minBytesForImages;
    }

    protected $enableImageFetching = true;

    public function setEnableImageFetching(bool $enableImageFetching) {
        $this->enableImageFetching = $enableImageFetching;
    }

    public function getEnableImageFetching() {
        return $this->enableImageFetching;
    }

    protected $timeout = 10000;

    public function setTimeout(int $timeout) {
        $this->timeout = $timeout;
    }

    public function getTimeout() {
        return $this->timeout;
    }

    protected $browserUserAgent = 'Mozilla/5.0 (X11; U; Linux x86_64; de; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8';

    public function setBrowserUserAgent(string $browserUserAgent) {
        $this->browserUserAgent = $browserUserAgent;
    }

    public function getBrowserUserAgent() {
        return $this->browserUserAgent;
    }

    protected $contentExtractor;

    public function setContentExtractor($contentExtractor) {
        if (is_null($contentExtractor) || !is_object($contentExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->contentExtractor = $contentExtractor;
    }

    public function getContentExtractor() {
        return $this->contentExtractor;
    }

    protected $publishDateExtractor;

    public function setPublishDateExtractor($publishDateExtractor) {
        if (is_null($publishDateExtractor) || !is_object($publishDateExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->publishDateExtractor = $publishDateExtractor;
    }

    public function getPublishDateExtractor() {
        return $this->publishDateExtractor;
    }

    protected $additionalDataExtractor;

    public function setAdditionalDataExtractor($additionalDataExtractor) {
        if (is_null($additionalDataExtractor) || !is_object($additionalDataExtractor)) {
            throw new InvalidArgumentException('Extractor must be a valid object!');
        }

        $this->additionalDataExtractor = $additionalDataExtractor;
    }

    public function getAdditionalDataExtractor() {
        return $this->additionalDataExtractor;
    }

    protected $stopWords;

    public function setStopWords($stopWords) {
        if (is_null($stopWords) || !is_object($stopWords)) {
            throw new InvalidArgumentException('Stop words must be a valid object!');
        }

        $this->stopWords = $stopWords;
    }

    public function getStopWords() {
        return $this->stopWords;
    }
}