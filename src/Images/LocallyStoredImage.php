<?php declare(strict_types=1);

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

    /**
     * remove unnecessary tmp image files
     */
    public function __destruct() {
        unlink($this->getLocalFileName());
    }
    
    /** @var string */
    private $imgSrc = '';

    /**
     * @param string $imgSrc
     *
     * @return self
     */
    public function setImgSrc(string $imgSrc): self {
        $this->imgSrc = $imgSrc;

        return $this;
    }

    /**
     * @return string
     */
    public function getImgSrc(): string {
        return $this->imgSrc;
    }

    /** @var string */
    private $localFileName = '';

    /**
     * @param string $localFileName
     *
     * @return self
     */
    public function setLocalFileName(string $localFileName): self {
        $this->localFileName = $localFileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocalFileName(): string {
        return $this->localFileName;
    }

    /** @var string */
    private $linkhash = '';

    /**
     * @param string $linkhash
     *
     * @return self
     */
    public function setLinkhash(string $linkhash): self {
        $this->linkhash = $linkhash;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinkhash(): string {
        return $this->linkhash;
    }

    /** @var int */
    private $bytes = 0;

    /**
     * @param int $bytes
     *
     * @return self
     */
    public function setBytes(int $bytes): self {
        $this->bytes = $bytes;

        return $this;
    }

    /**
     * @return int
     */
    public function getBytes(): int {
        return $this->bytes;
    }

    /** @var string */
    private $fileExtension = '';

    /**
     * @param string $fileExtension
     *
     * @return self
     */
    public function setFileExtension(string $fileExtension): self {
        $this->fileExtension = $fileExtension;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileExtension(): string {
        return $this->fileExtension;
    }

    /** @var int */
    private $height = 0;

    /**
     * @param int $height
     *
     * @return self
     */
    public function setHeight(int $height): self {
        $this->height = $height;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight(): int {
        return $this->height;
    }

    /** @var int */
    private $width = 0;

    /**
     * @param int $width
     *
     * @return self
     */
    public function setWidth(int $width): self {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): int {
        return $this->width;
    }
}
