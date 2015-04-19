<?php

namespace Goose\Text;

class WordStats
{
    /** @var int Total number of stopwords or good words that we can calculate */
    private $stopWordCount = 0;

    /** @var int Total number of words on a node */
    private $wordCount = 0;

    /** @var array Holds an actual list of the stop words we found */
    private $stopWords = [];

    public function __construct($options = []) {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }

    public function getStopWords() {
        return $this->stopWords;
    }

    public function setStopWords($words) {
        $this->stopWords = $words;
    }

    public function getStopWordCount() {
        return $this->stopWordCount;
    }

    public function setStopWordCount($wordcount) {
        $this->stopWordCount = $wordcount;
    }

    public function getWordCount() {
        return $this->wordCount;
    }

    public function setWordCount($cnt) {
        $this->wordCount = $cnt;
    }
}