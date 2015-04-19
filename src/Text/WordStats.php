<?php

namespace Goose\Text;

/**
 * Word Stats
 *
 * @package Goose\Text
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class WordStats
{
    /** @var int Total number of stopwords or good words that we can calculate */
    private $stopWordCount = 0;

    /** @var int Total number of words on a node */
    private $wordCount = 0;

    /** @var array Holds an actual list of the stop words we found */
    private $stopWords = [];

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
     * @return string[]
     */
    public function getStopWords() {
        return $this->stopWords;
    }

    /**
     * @param string[] $words
     */
    public function setStopWords($words) {
        $this->stopWords = $words;
    }

    /**
     * @return int
     */
    public function getStopWordCount() {
        return $this->stopWordCount;
    }

    /**
     * @param int $wordcount
     */
    public function setStopWordCount($wordcount) {
        $this->stopWordCount = $wordcount;
    }

    /**
     * @return int
     */
    public function getWordCount() {
        return $this->wordCount;
    }

    /**
     * @param int $cnt
     */
    public function setWordCount($cnt) {
        $this->wordCount = $cnt;
    }
}