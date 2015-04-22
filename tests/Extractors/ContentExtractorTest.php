<?php

namespace Goose\Tests\Extractors;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;
use Goose\Extractors\ContentExtractor;

class ContentExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTitleProvider
     */
    public function testGetTitle($expected, $article, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getTitle($article),
            $message
        );
    }

    public function getTitleProvider() {
        return [
            ['Ut venenatis rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>Ut venenatis rutrum ex, eu feugiat dolor.</title></head></html>'), 'No splitter'],
            ['rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>Ut venenatis | rutrum ex, eu feugiat dolor.</title></head></html>'), 'Pipe splitter'],
            ['rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>Ut venenatis - rutrum ex, eu feugiat dolor.</title></head></html>'), 'Dash splitter'],
            ['rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>Ut venenatis » rutrum ex, eu feugiat dolor.</title></head></html>'), 'Right pointing guillemet splitter'],
            ['rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>Ut venenatis : rutrum ex, eu feugiat dolor.</title></head></html>'), 'Colon splitter'],
            ['', $this->generate('<html><title>Ut venenatis rutrum ex, eu feugiat dolor.</title></html>'), 'Title tag not in head tag'],
            ['', $this->generate('<html></html>'), 'No title tag'],
            ['Ut venenatis rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>Ut venenatis rutrum ex, eu feugiat dolor. |</title></head></html>'), 'Splitter as last character'],
            ['Ut venenatis rutrum ex, eu feugiat dolor.', $this->generate('<html><head><title>|Ut venenatis rutrum ex, eu feugiat dolor.</title></head></html>'), 'Splitter as first character'],
            ['|', $this->generate('<html><head><title>|</title></head></html>'), 'Splitter as only character'],
        ];
    }

    /**
     * @dataProvider getMetaLanguageProvider
     */
    public function testGetMetaLanguage($expected, $article, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getMetaLanguage($article),
            $message
        );
    }

    public function getMetaLanguageProvider() {
        return [
            ['en', $this->generate('<html lang="en"><head></head></html>'), 'Two letter language code in html lang attribute'],
            ['en-AU', $this->generate('<html lang="en-AU"><head></head></html>'), 'Two letter language code with extended language code in html lang attribute'],
            ['sga', $this->generate('<html lang="sga"><head></head></html>'), 'Three letter language code html lang attribute'],
            ['zh-Hant-HK', $this->generate('<html lang="zh-Hant-HK"><head></head></html>'), 'Two letter language code with extended language code and script in html lang attribute'],
            ['en', $this->generate('<html><head><meta name="lang" content="en" /></head></html>'), 'Two letter language code in meta lang tag'],
            ['en-AU', $this->generate('<html><head><meta name="lang" content="en-AU" /></head></html>'), 'Two letter language code with extended language code in meta lang tag'],
            ['sga', $this->generate('<html><head><meta name="lang" content="sga" /></head></html>'), 'Three letter language code meta lang tag'],
            ['zh-Hant-HK', $this->generate('<html><head><meta name="lang" content="zh-Hant-HK" /></head></html>'), 'Two letter language code with extended language code and script in meta lang tag'],
            ['en', $this->generate('<html><head><meta name="http-equiv=content-language" content="en" /></head></html>'), 'Two letter language code in meta http-equiv=content-language tag'],
            ['en-AU', $this->generate('<html><head><meta name="http-equiv=content-language" content="en-AU" /></head></html>'), 'Two letter language code with extended language code in meta http-equiv=content-language tag'],
            ['sga', $this->generate('<html><head><meta name="http-equiv=content-language" content="sga" /></head></html>'), 'Three letter language code meta http-equiv=content-language tag'],
            ['zh-Hant-HK', $this->generate('<html><head><meta name="http-equiv=content-language" content="zh-Hant-HK" /></head></html>'), 'Two letter language code with extended language code and script in meta http-equiv=content-language tag'],
            ['', $this->generate('<html><meta name="http-equiv=content-language" content="en" /></html>'), 'Meta tag not inside head tag'],
        ];
    }

    /**
     * @dataProvider getMetaDescriptionProvider
     */
    public function testGetMetaDescription($expected, $article, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getMetaDescription($article),
            $message
        );
    }

    public function getMetaDescriptionProvider() {
        return [
            ['Vivamus mattis ut felis ut fermentum.', $this->generate('<html><head><meta name="description" content="Vivamus mattis ut felis ut fermentum." /></head></html>'), 'Meta description tag'],
            ['Vivamus mattis ut felis ut fermentum.', $this->generate('<html><head><meta property="og:description" content="Vivamus mattis ut felis ut fermentum." /></head></html>'), 'Meta og:description tag'],
            ['Vivamus mattis ut felis ut fermentum.', $this->generate('<html><head><meta name="twitter:description" content="Vivamus mattis ut felis ut fermentum." /></head></html>'), 'Meta twitter:description tag'],
            ['', $this->generate('<html><meta name="description" content="Vivamus mattis ut felis ut fermentum." /></html>'), 'Meta description tag not inside head tag'],
            ['', $this->generate('<html><meta property="og:description" content="Vivamus mattis ut felis ut fermentum." /></html>'), 'Meta og:description tag not inside head tag'],
            ['', $this->generate('<html><meta name="twitter:description" content="Vivamus mattis ut felis ut fermentum." /></html>'), 'Meta twitter:description tag not inside head tag'],
            ['', $this->generate('<html><head><meta name="description" property="Vivamus mattis ut felis ut fermentum." /></head></html>'), 'Meta tag with no content attribute'],
        ];
    }

    /**
     * @dataProvider getMetaKeywordsProvider
     */
    public function testGetMetaKeywords($expected, $article, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getMetaKeywords($article),
            $message
        );
    }

    public function getMetaKeywordsProvider() {
        return [
            ['Etiam aliquam, ligula ut urna, lacinia porta', $this->generate('<html><head><meta name="keywords" content="Etiam aliquam, ligula ut urna, lacinia porta" /></head></html>'), 'Meta keyword tag'],
            ['', $this->generate('<html><meta name="keywords" content="Etiam aliquam, ligula ut urna, lacinia porta" /></html>'), 'Meta keywords tag not inside head tag'],
        ];
    }

    /**
     * @dataProvider getCanonicalLinkProvider
     */
    public function testGetCanonicalLink($expected, $article, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getCanonicalLink($article),
            $message
        );
    }

    public function getCanonicalLinkProvider() {
        $article = $this->generate('<html></html>');
        $article->setFinalUrl('http://example.org/no-canonical');

        return [
            ['http://example.org/ok', $this->generate('<html><head><link rel="canonical" href="http://example.org/ok" /></head></html>'), 'Link canonical tag'],
            ['', $this->generate('<html><head><link rel="canonical" src="http://example.org/ok" /></head></html>'), 'Link canonical tag without valid href attribute'],
            ['http://example.org/ok', $this->generate('<html><head><meta property="og:url" content="http://example.org/ok" /></head></html>'), 'Meta og:url tag'],
            ['', $this->generate('<html><head><meta property="og:url" href="http://example.org/ok" /></head></html>'), 'Meta og:url tag with no property attribute'],
            ['http://example.org/ok', $this->generate('<html><head><meta name="twitter:url" content="http://example.org/ok" /></head></html>'), 'Meta twitter:url tag'],
            ['', $this->generate('<html><head><meta name="twitter:url" href="http://example.org/ok" /></head></html>'), 'Meta twitter:url tag with no name attribute'],
            [null, $this->generate('<html><head></head></html>'), 'No canonical links without article url set'],
            ['http://example.org/no-canonical', $article, 'No canonical links with article url set'],
        ];
    }

    /**
     * @dataProvider getDateFromURLProvider
     */
    public function testGetDateFromURL($expected, $url, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getDateFromURL($url),
            $message
        );
    }

    public function getDateFromURLProvider() {
        return [
            ['', 'http://example.org/', 'Stub']
        ];
    }

    /**
     * @dataProvider getDomainProvider
     */
    public function testGetDomain($expected, $url, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getDomain($url),
            $message
        );
    }

    public function getDomainProvider() {
        return [
            ['example.org', 'https://example.org:80/directory/index.html', 'Domain name #1'],
            ['subdomain.example.org', 'https://subdomain.example.org:80/', 'Domain name #2'],
        ];
    }

    /**
     * @dataProvider extractTagsProvider
     */
    public function testExtractTags($expected, $url, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->extractTags($url),
            $message
        );
    }

    public function extractTagsProvider() {
        return [
            [['Example'], $this->generate('<html><body><a href="/dir/tag/test">Example</a></body></html>'), 'A href tag test #1'],
            [[], $this->generate('<html><body><a href="/dir/tag">Example</a></body></html>'), 'A href tag test #2'],
            [['Example'], $this->generate('<html><body><a href="/tag/test">Example</a></body></html>'), 'A href tag test #3'],
            [[], $this->generate('<html><body><a href="tag/test">Example</a></body></html>'), 'A href tag test #4'],
            [['Example'], $this->generate('<html><body><a rel="tag" href="/dir/test">Example</a></body></html>'), 'A rel tag test #1'],
            [[], $this->generate('<html><body></body></html>'), 'Empty test'],
        ];
    }

    /**
     * @dataProvider calculateBestNodeBasedOnClusteringProvider
     */
    public function testCalculateBestNodeBasedOnClustering($expected, $url, $message)
    {
        // TODO
    }

    public function calculateBestNodeBasedOnClusteringProvider() {
        return [
        ];
    }

    /**
     * @dataProvider getShortTextProvider
     */
    public function testGetShortText($expected, $str, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->getShortText($str, 10),
            $message
        );
    }

    public function getShortTextProvider() {
        return [
            ['Sed dignis...', 'Sed dignissim dolor.', 'Text #1'],
            ['Sed dignis', 'Sed dignis', 'Text #2'],
        ];
    }

    /**
     * @dataProvider extractVideosProvider
     */
    public function testExtractVideos($expected, $document, $message)
    {
        $obj = new ContentExtractor($this->config());

        $this->assertEquals(
            $expected,
            $obj->extractVideos($document->filter('div#test')->first()),
            $message
        );
    }

    public function extractVideosProvider() {
        return [
            [
                ['https://www.youtube.com/watch?v=oHg5SJYRHA0'],
                $this->document('<html><body><div id="test"><iframe src="https://www.youtube.com/watch?v=oHg5SJYRHA0"></iframe></div></body></html>'),
                'youtube #1 - iframe',
            ],
            [
                ['https://www.youtube.com/watch?v=oHg5SJYRHA0'],
                $this->document('<html><body><div id="test"><object width="720" height="480"><embed src="https://www.youtube.com/watch?v=oHg5SJYRHA0"></embed></object></div></body></html>'),
                'youtube #1 - embed',
            ],
            [
                ['https://www.youtube.com/watch?v=oHg5SJYRHA0'],
                $this->document('<html><body><div id="test"><object width="720" height="480" data="https://www.youtube.com/watch?v=oHg5SJYRHA0"></object></div></body></html>'),
                'youtube #1 - object',
            ],
        ];
    }

    public function html($doc) {
        if ($doc instanceof DOMDocument) {
            $el = $doc->documentElement;
        } else if ($doc instanceof DOMElement) {
            $el = $doc;
            $doc = $doc->ownerDocument;
        } else if ($doc instanceof Article) {
            $doc = $doc->getDoc();
            $el = $doc->documentElement;
        }

        return $doc->saveXML($el);
    }

    private function document($html) {
        $doc = new DOMDocument(1.0);
        $doc->registerNodeClass('DOMElement', 'Goose\\DOM\\DOMElement');

        // Silence 'Tag xyz invalid in Entity' for HTML5 tags.
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);

        return $doc;
    }

    private function generate($html) {
        $article = new Article();
        $article->setDoc($this->document($html));

        return $article;
    }

    private function config() {
        $config = new Configuration();

        return $config;
    }
}