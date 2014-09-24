<?php

namespace Goose\Text;

class WordStats
{
    /**
     * total number of stopwords or good words that we can calculate
     */
    private $stopWordCount = 0;

    /**
     * total number of words on a node
     */
    private $wordCount = 0;

    /**
     * holds an actual list of the stop words we found
     */
    private $stopWords = [];

    public function __construct($options = array()) {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), $value);
            }
        }
    }

    public function getStopWords() {
        return $stopWords;
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