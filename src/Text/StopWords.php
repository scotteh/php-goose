<?php

namespace Goose\Text;

use Goose\Configuration;

/**
 * Stop Words
 *
 * @package Goose\Text
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class StopWords
{
    /** @var Configuration */
    private $config;

    /** @var array */
    private $cached = [];

    /** @var string[] */
    private $languages = [
        'ar', 'da', 'de', 'en', 'es', 'fi',
        'fr', 'hu', 'id', 'it', 'ko', 'nb',
        'nl', 'no', 'pl', 'pt', 'ru', 'sv',
        'zh'
    ];

    /**
     * @param Configuration $config
     * @param string $language
     */
    public function __construct(Configuration $config, $language) {
        $this->config = $config;

        if (!in_array($language, $this->languages)) {
            $language = 'en';
        }

        $file = sprintf(__DIR__ . '/../../resources/text/stopwords-%s.txt', $language);

        $this->cached = explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($file)));
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function removePunctuation($str) {
        return preg_replace("/[[:punct:]]+/", '', $str);
    }

    /**
     * @param string $content
     *
     * @return WordStats
     */
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

    /**
     * @param string $content
     */
    public function getCurrentStopWords() {
        return $this->cached;
    }
}