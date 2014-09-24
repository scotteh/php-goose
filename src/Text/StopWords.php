<?php

namespace Goose\Text;

class StopWords
{
    private $config;
    private $cached = [];

    public function __construct($config, $language) {
        $this->config = $config;

        $file = sprintf(__DIR__ . '/../../resources/text/stopwords-%s.txt', $language);

        $this->cached = explode("\n", file_get_contents($file));
    }

    public function removePunctuation($str) {
        return preg_replace("/[[:punct:]]+/", '', $str);
    }

    public function getStopwordCount($content) {
        if (empty($content)) {
            return new WordStats();
        }

        $strippedInput = $this->removePunctuation($content);
        $candidateWords = explode(' ', $strippedInput);

        $overlappingStopWords = [];
        foreach ($candidateWords as $w) {
            if (in_array(strtolower($w), $this->cached)) {
                $overlappingStopWords[] = strtolower($w);
            }
        }

        return new WordStats([
            'wordCount' => count($candidateWords),
            'stopWordCount' => count($overlappingStopWords),
            'stopWords' => $overlappingStopWords,
        ]);
    }
}