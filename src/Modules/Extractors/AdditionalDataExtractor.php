<?php declare(strict_types=1);

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\Utils\Helper;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\{AbstractModule, ModuleInterface};
use DOMWrap\Element;

/**
 * Additional Data Extractor
 *
 * @package Goose\Modules\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class AdditionalDataExtractor extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait;

    /** @var string */
    protected static $A_REL_TAG_SELECTOR = "a[rel='tag'], a[href*='/tag/']";

    /** @var string[] */
    protected static $VIDEO_PROVIDERS = [
        'youtube\.com',
        'youtu\.be',
        'vimeo\.com',
        'blip\.tv',
        'dailymotion\.com',
        'dai\.ly',
        'flickr\.com',
        'flic\.kr',
    ];

    /** @inheritdoc */
    public function run(Article $article): self {
        $this->article($article);

        $article->setTags($this->getTags());

        if ($this->article()->getTopNode() instanceof Element) {
            $article->setVideos($this->getVideos());
            $article->setLinks($this->getLinks());
            $article->setPopularWords($this->getPopularWords());
        }

        return $this;
    }

    /**
     * @return string[]
     */
    private function getTags(): array {
        $nodes = $this->article()->getDoc()->find(self::$A_REL_TAG_SELECTOR);

        $tags = [];

        foreach ($nodes as $node) {
            $tags[] = Helper::textNormalise($node->text());
        }

        return $tags;
    }

    /**
     * Pulls out videos we like
     *
     * @return string[]
     */
    private function getVideos(): array {
        $videos = [];

        $parentNode = $this->article()->getTopNode()->parent();

        if ($parentNode instanceof Element) {
            $nodes = $parentNode->find('embed, object, iframe');

            foreach ($nodes as $node) {
                if ($node->hasAttribute('src')) {
                    $src = $node->attr('src');
                } else {
                    $src = $node->attr('data');
                }

                $match = array_reduce(self::$VIDEO_PROVIDERS, function($match, $domain) use ($src) {
                    $srcHost = parse_url($src, PHP_URL_HOST);
                    $srcScheme = parse_url($src, PHP_URL_SCHEME);

                    return $match || preg_match('@' . $domain . '$@i', $srcHost ?? '' ) && in_array($srcScheme, ['http', 'https']);
                });

                if ($match) {
                    $videos[] = $src;
                }
            }
        }

        return $videos;
    }

    /**
     * Pulls out links we like
     *
     * @return array
     */
    private function getLinks(): array {
        $goodLinks = [];

        $parentNode = $this->article()->getTopNode()->parent();

        if ($parentNode instanceof Element) {
            $candidates = $parentNode->find('a[href]');

            foreach ($candidates as $el) {
                if ($el->attr('href') != '#' && trim($el->attr('href')) != '') {
                    $goodLinks[] = [
                        'url' => $el->attr('href'),
                        'text' => Helper::textNormalise($el->text()),
                    ];
                }
            }
        }

        return $goodLinks;
    }

    /**
     * @return string[]
     */
    private function getPopularWords(): array {
        $limit = 5;
        $minimumFrequency = 1;
        $stopWords = $this->config()->getStopWords()->getWordList();

        $text = $this->article()->getTitle();
        $text .= ' ' . $this->article()->getMetaDescription();

        if ($this->article()->getTopNode()) {
            $text .= ' ' . $this->article()->getCleanedArticleText();
        }

        // Decode and split words by white-space
        $text = html_entity_decode($text, ENT_COMPAT | ENT_HTML5, 'UTF-8');
        $words = preg_split('@[\s]+@iu', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (!is_array($words)) {
            return array();
        }

        // Determine stop words currently in $words
        $ignoreWords = array_intersect($words, $stopWords);
        // Remove ignored words from $words
        $words = array_udiff($words, $ignoreWords, 'strcasecmp');

        // Count and sort $words
        $words = array_count_values($words);
        arsort($words);

        // Limit and filter $words
        $words = array_slice($words, 0, $limit);
        $words = array_filter($words, function($value) use ($minimumFrequency){
            return !($value < $minimumFrequency);
        });

        return $words;
    }
}
