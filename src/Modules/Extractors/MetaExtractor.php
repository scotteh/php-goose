<?php

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\DOM\DOMDocument;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMNodeList;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\AbstractModule;
use Goose\Modules\ModuleInterface;

/**
 * Content Extractor
 *
 * @package Goose\Modules\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class MetaExtractor extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait;

    /** @var string[] */
    protected static $SPLITTER_CHARS = [
        '|', '-', '»', ':',
    ];

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

    /**
     * @param Article $article
     */
    public function run(Article $article) {
        $this->article($article);

        $article->setTitle($this->getTitle());
        $article->setMetaDescription($this->getMetaDescription());
        $article->setMetaKeywords($this->getMetaKeywords());
        $article->setCanonicalLink($this->getCanonicalLink());
        $article->setTags($this->getTags());

        if ($this->article()->getTopNode() instanceof DOMElement) {
            $article->setVideos($this->getVideos());
            $article->setLinks($this->getLinks());
            $article->setPopularWords($this->getPopularWords());
        }

        $article->setLanguage($this->getMetaLanguage());

        $this->config()->set('language', $article->getLanguage());
    }

    /**
     * @return string
     */
    private function getTitle() {
        $nodes = $this->article()->getDoc()->filter('html > head > title');

        if (!$nodes->count()) return '';

        $title = $nodes->first()->text(DOM_NODE_TEXT_NORMALISED);

        foreach (self::$SPLITTER_CHARS as $char) {
            if (strpos($title, $char) !== false) {
                $part = array_reduce(explode($char, $title), function($carry, $item) {
                    if (mb_strlen($item) > mb_strlen($carry)) {
                        return $item;
                    }

                    return $carry;
                });

                if (!empty($part)) {
                    $title = $part;
                    break;
                }
            }
        }

        return trim($title);
    }

    /**
     * @param DOMDocument $doc
     * @param string $tag
     * @param string $property
     * @param string $value
     *
     * @return DOMNodeList
     */
    private function getNodesByLowercasePropertyValue(DOMDocument $doc, $tag, $property, $value) {
        return $doc->filterXPath("descendant::".$tag."[translate(@".$property.", 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='".$value."']");
    }

    /**
     * @param DOMDocument $doc
     * @param string $property
     * @param string $value
     * @param string $attr
     *
     * @return string
     */
    private function getMetaContent(DOMDocument $doc, $property, $value, $attr = 'content') {
        $nodes = $this->getNodesByLowercasePropertyValue($doc, 'meta', $property, $value);

        if (!$nodes->count()) {
            return '';
        }

        $content = $nodes->first()->getAttribute($attr);
        $content = trim($content);

        return $content;
    }

    /**
     * If the article has meta language set in the source, use that
     *
     * @return string
     */
    private function getMetaLanguage() {
        $lang = '';

        $el = $this->article()->getDoc()->filter('html[lang]');

        if ($el->count()) {
            $lang = $el->first()->getAttribute('lang');
        }

        if (empty($lang)) {
            $selectors = [
                'html > head > meta[http-equiv=content-language]',
                'html > head > meta[name=lang]',
            ];

            foreach ($selectors as $selector) {
                $el = $this->article()->getDoc()->filter($selector);

                if ($el->count()) {
                    $lang = $el->first()->getAttribute('content');
                    break;
                }
            }
        }

        if (preg_match('@^[A-Za-z]{2}$@', $lang)) {
            return strtolower($lang);
        }

        return '';
    }

    /**
     * If the article has meta description set in the source, use that
     *
     * @return string
     */
    private function getMetaDescription() {
        $desc = $this->getMetaContent($this->article()->getDoc(), 'name', 'description');

        if (empty($desc)) {
            $desc = $this->getMetaContent($this->article()->getDoc(), 'property', 'og:description');
        }

        if (empty($desc)) {
            $desc = $this->getMetaContent($this->article()->getDoc(), 'name', 'twitter:description');
        }

        return trim($desc);
    }

    /**
     * If the article has meta keywords set in the source, use that
     *
     * @return string
     */
    private function getMetaKeywords() {
        return $this->getMetaContent($this->article()->getDoc(), 'name', 'keywords');
    }

    /**
     * If the article has meta canonical link set in the url
     *
     * @return string
     */
    private function getCanonicalLink() {
        $nodes = $this->getNodesByLowercasePropertyValue($this->article()->getDoc(), 'link', 'rel', 'canonical');

        if ($nodes->count()) {
            return trim($nodes->first()->getAttribute('href'));
        }

        $nodes = $this->getNodesByLowercasePropertyValue($this->article()->getDoc(), 'meta', 'property', 'og:url');

        if ($nodes->count()) {
            return trim($nodes->first()->getAttribute('content'));
        }

        $nodes = $this->getNodesByLowercasePropertyValue($this->article()->getDoc(), 'meta', 'name', 'twitter:url');

        if ($nodes->count()) {
            return trim($nodes->first()->getAttribute('content'));
        }

        return $this->article()->getFinalUrl();
    }

    /**
     * @return string[]
     */
    private function getTags() {
        $nodes = $this->article()->getDoc()->filter(self::$A_REL_TAG_SELECTOR);

        $tags = [];

        foreach ($nodes as $node) {
            $tags[] = $node->text(DOM_NODE_TEXT_NORMALISED);
        }

        return $tags;
    }

    /**
     * Pulls out videos we like
     *
     * @return string[]
     */
    private function getVideos() {
        $videos = [];

        $nodes = $this->article()->getTopNode()->parent()->filter('embed, object, iframe');

        foreach ($nodes as $node) {
            if ($node->hasAttribute('src')) {
                $src = $node->getAttribute('src');
            } else {
                $src = $node->getAttribute('data');
            }

            $match = array_reduce(self::$VIDEO_PROVIDERS, function($match, $domain) use ($src) {
                $srcHost = parse_url($src, PHP_URL_HOST);
                $srcScheme = parse_url($src, PHP_URL_SCHEME);

                return $match || preg_match('@' . $domain . '$@i', $srcHost) && in_array($srcScheme, ['http', 'https']);
            });

            if ($match) {
                $videos[] = $src;
            }
        }

        return $videos;
    }

    /**
     * Pulls out links we like
     *
     * @return string[]
     */
    private function getLinks() {
        $goodLinks = [];

        $candidates = $this->article()->getTopNode()->parent()->filter('a[href]');

        foreach ($candidates as $el) {
            if ($el->getAttribute('href') != '#' && trim($el->getAttribute('href')) != '') {
                $goodLinks[] = [
                    'url' => $el->getAttribute('href'),
                    'text' => $el->text(DOM_NODE_TEXT_NORMALISED),
                ];
            }
        }

        return $goodLinks;
    }

    /**
     * @return string[]
     */
    private function getPopularWords() {
        $limit = 5;
        $minimumFrequency = 1;
        $stopWords = $this->config()->getStopWords()->getCurrentStopWords();

        $text = $this->article()->getTitle();
        $text .= ' ' . $this->article()->getMetaDescription();

        if ($this->article()->getTopNode()) {
            $text .= ' ' . $this->article()->getCleanedArticleText();
        }

        // Decode and split words by white-space
        $text = html_entity_decode($text, ENT_COMPAT | ENT_HTML5, 'UTF-8');
        $words = preg_split('@[\s]+@iu', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Determine stop words currently in $words
        $ignoreWords = array_intersect($words, $stopWords);
        // Remove ignored words from $words
        $words = array_diff($words, $ignoreWords);

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
