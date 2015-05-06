<?php

namespace Goose\Images;

use DOMWrap\Element;

/**
 * Image
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Image {
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

    /** @var Element */
    private $topImageNode = null;

    /**
     * @param Element $topImageNode
     */
    public function setTopImageNode($topImageNode) {
        $this->topImageNode = $topImageNode;
    }

    /**
     * @return Element
     */
    public function getTopImageNode() {
        return $this->topImageNode;
    }

    /** @var string */
    private $imageSrc = '';

    /**
     * @param string $imageSrc
     */
    public function setImageSrc($imageSrc) {
        $this->imageSrc = $imageSrc;
    }

    /**
     * @return string
     */
    public function getImageSrc() {
        return $this->imageSrc;
    }

    /** @var double */
    private $imageScore = 0.0;

    /**
     * @param double $imageScore
     */
    public function setImageScore($imageScore) {
        $this->imageScore = $imageScore;
    }

    /**
     * @return double
     */
    public function getImageScore() {
        return $this->imageScore;
    }

    /** @var double */
    private $confidenceScore = 0.0;

    /**
     * @param double $confidenceScore
     */
    public function setConfidenceScore($confidenceScore) {
        $this->confidenceScore = $confidenceScore;
    }

    /**
     * @return double
     */
    public function getConfidenceScore() {
        return $this->confidenceScore;
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

    /** @var string */
    private $imageExtractionType = 'NA';

    /**
     * @param string $imageExtractionType
     */
    public function setImageExtractionType($imageExtractionType) {
        $this->imageExtractionType = $imageExtractionType;
    }

    /**
     * @return string
     */
    public function getImageExtractionType() {
        return $this->imageExtractionType;
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
}
