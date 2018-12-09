<?php declare(strict_types=1);

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

    /** @var string[] */
    private $cached = [];

    /** @var string[] */
    private $languages = [
        'ar', 'cs', 'da', 'de', 'en', 'es',
        'fi', 'fr', 'hu', 'id', 'it', 'ja',
        'ko', 'nb', 'nl', 'no', 'pl', 'pt',
        'ru', 'sv', 'zh', 'vi'
    ];

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @return Configuration
     */
    public function config(): Configuration {
        return $this->config;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function removePunctuation(string $str): string {
        return preg_replace("/[[:punct:]]+/", '', $str);
    }

    /**
     * @return string
     */
    public function getLanguage(): string {
        list($language) = explode('-', $this->config()->get('language'));

        if (!in_array($language, $this->languages)) {
            $language = 'en';
        }
        return mb_strtolower($language);
    }

    /**
     * @return mixed
     */
    public function getWordList(): array {
        if (empty($this->cached)) {
            $file = sprintf(__DIR__ . '/../../resources/text/stopwords-%s.txt', $this->getLanguage());

            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($file)));

            $this->cached = array_filter($lines, function($line) {
                // Ignore emoty lines and lines starting with '#'.
                return !(trim($line) == '' || mb_substr($line, 0, 1) == '#');
            });
        }

        return $this->cached;
    }

    /**
     * @param string $content
     *
     * @return WordStats
     */
    public function getStopwordCount(string $content): WordStats {
        if (empty($content)) {
            return new WordStats();
        }

        $strippedInput = $this->removePunctuation($content);
        $candidateWords = $this->getCandidateWords($strippedInput);

        $overlappingStopWords = [];
        foreach ($candidateWords as $w) {
            if (in_array(mb_strtolower($w), $this->getWordList())) {
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
     * @param string $strippedInput
     *
     * @return array
     */
    public function getCandidateWords(string $strippedInput): array {
        // Simple separating words in Japanese.
        if ($this->getLanguage() === 'ja') {
            $regexp = '/(' . implode('|', array_map('preg_quote', $this->getWordList())) . ')/';
            $strippedInput = preg_replace($regexp, ' $1 ', $strippedInput);
        }

        return explode(' ', $strippedInput);
    }
}