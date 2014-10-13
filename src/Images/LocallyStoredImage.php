<?php

namespace Goose\Images;

class LocallyStoredImage {
    public function __construct($options = []) {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }

    private $imgSrc = '';

    public function setImgSrc($imgSrc) {
        $this->imgSrc = $imgSrc;
    }

    public function getImgSrc() {
        return $this->imgSrc;
    }

    private $localFileName = '';

    public function setLocalFileName($localFileName) {
        $this->localFileName = $localFileName;
    }

    public function getLocalFileName() {
        return $this->localFileName;
    }

    private $linkhash = '';

    public function setLinkhash($linkhash) {
        return $this->linkhash;
    }

    public function getLinkhash() {
        return $this->linkhash;
    }

    private $bytes = 0;

    public function setBytes($bytes) {
        $this->bytes = $bytes;
    }

    public function getBytes() {
        return $this->bytes;
    }

    private $fileExtension = '';

    public function setFileExtension($fileExtension) {
        $this->fileExtension = $fileExtension;
    }

    public function getFileExtension() {
        return $this->fileExtension;
    }

    private $height = 0;

    public function setHeight($height) {
        $this->height = $height;
    }

    public function getHeight() {
        return $this->height;
    }

    private $width = 0;

    public function setWidth($width) {
        $this->width = $width;
    }

    public function getWidth() {
        return $this->width;
    }
}