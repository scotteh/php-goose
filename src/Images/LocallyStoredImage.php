<?php

namespace Goose\Images;

/**
 * Locally Stored Images
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class LocallyStoredImage {
    /**
     * @param mixed[] $options
     */
    public function __construct($options = []) {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }

    /** @var string */
    private $imgSrc = '';

    /**
     * @param string $imgSrc
     */
    public function setImgSrc($imgSrc) {
        $this->imgSrc = $imgSrc;
    }

    /**
     * @return string
     */
    public function getImgSrc() {
        return $this->imgSrc;
    }

    /** @var string */
    private $localFileName = '';

    /**
     * @param string $localFileName
     */
    public function setLocalFileName($localFileName) {
        $this->localFileName = $localFileName;
    }

    /**
     * @return string
     */
    public function getLocalFileName() {
        return $this->localFileName;
    }

    /** @var string */
    private $linkhash = '';

    /**
     * @param string $linkhash
     */
    public function setLinkhash($linkhash) {
        $this->linkhash = $linkhash;
    }

    /**
     * @return string
     */
    public function getLinkhash() {
        return $this->linkhash;
    }

    /** @var int */
    private $bytes = 0;

    /**
     * @param int $bytes
     */
    public function setBytes($bytes) {
        $this->bytes = $bytes;
    }

    /**
     * @return int
     */
    public function getBytes() {
        return $this->bytes;
    }

    /** @var string */
    private $fileExtension = '';

    /**
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension) {
        $this->fileExtension = $fileExtension;
    }

    /**
     * @return string
     */
    public function getFileExtension() {
        return $this->fileExtension;
    }

    /** @var int */
    private $height = 0;

    /**
     * @param int $height
     */
    public function setHeight($height) {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getHeight() {
        return $this->height;
    }

    /** @var int */
    private $width = 0;

    /**
     * @param int $width
     */
    public function setWidth($width) {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getWidth() {
        return $this->width;
    }
}