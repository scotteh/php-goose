<?php

namespace Goose\Text;

class StopWords
{
    private $config;
    private $cached = [];
    private $languages = [
        'ar', 'da', 'de', 'en', 'es', 'fi',
        'fr', 'hu', 'id', 'it', 'ko', 'nb',
        'nl', 'no', 'pl', 'pt', 'ru', 'sv',
        'zh'
    ];

    public function __construct($config, $language) {
        $this->config = $config;

        if (!in_array($language, $this->languages)) {
            $language = 'en';
        }

        $file = sprintf(__DIR__ . '/../../resources/text/stopwords-%s.txt', $language);

        $this->cached = explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($file)));
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
            if (in_array(mb_strtolower($w), $this->cached)) {
                $overlappingStopWords[] = mb_strtolower($w);
            }
        }

        return new WordStats([
            'wordCount' => count($candidateWords),
            'stopWordCount' => count($overlappingStopWords),
            'stopWords' => $overlappingStopWords,
        ]);
    }

    public function getCurrentStopWords()
    {
        return $this->cached;
    }
}