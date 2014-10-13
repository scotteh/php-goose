<?php

namespace Goose\Images;

class Image {
    public function __construct($options = []) {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }

    private $topImageNode = null;

    public function setTopImageNode($topImageNode) {
        $this->topImageNode = $topImageNode;
    }

    public function getTopImageNode() {
        return $this->topImageNode;
    }

    private $imageSrc = '';

    public function setImageSrc($imageSrc) {
        $this->imageSrc = $imageSrc;
    }

    public function getImageSrc() {
        return $this->imageSrc;
    }

    private $imageScore = 0;

    public function setImageScore($imageScore) {
        $this->imageScore = $imageScore;
    }

    public function getImageScore() {
        return $this->imageScore;
    }

    private $confidenceScore = 0.0;

    public function setConfidenceScore($confidenceScore) {
        $this->confidenceScore = $confidenceScore;
    }

    public function getConfidenceScore() {
        return $this->confidenceScore;
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

    private $imageExtractionType = 'NA';

    public function setImageExtractionType($imageExtractionType) {
        $this->imageExtractionType = $imageExtractionType;
    }

    public function getImageExtractionType() {
        return $this->imageExtractionType;
    }

    private $bytes = 0;

    public function setBytes($bytes) {
        $this->bytes = $bytes;
    }

    public function getBytes() {
        return $this->bytes;
    }
}
