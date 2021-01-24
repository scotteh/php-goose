<?php

namespace Goose\Tests\Modules\Cleaners;

use Goose\Article;

class DocumentCleanerTest extends \PHPUnit\Framework\TestCase
{
    use \Goose\Tests\Harness\TestTrait;

    private static $CLASS_NAME = '\Goose\Modules\Cleaners\DocumentCleaner';

    /**
     * @dataProvider removeXPathProvider
     */
    public function testRemoveXPath($expected, $article, $xpath, $message)
    {
        $this->setDocument($article->getDoc());

        $this->call('removeXPath', $xpath);

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $article->getDoc()->saveXml($article->getDoc()->documentElement),
            $message
        );
    }

    public function removeXPathProvider() {
        return [
            [
                '<html><body></body></html>',
                $this->generate('<html><body><!-- Comment --></body></html>'),
                '//comment()',
                'Single Line Comment'
            ],
            [
                '<html><body></body></html>',
                $this->generate("<html><body><!-- \n Comment \n --></body></html>"),
                '//comment()',
                'Multi Line Comment'
            ],
        ];
    }

    /**
     * @dataProvider replaceProvider
     */
    public function testReplace($expected, $article, $selector, $message)
    {
        $this->setDocument($article->getDoc());

        $this->call('replace', $selector);

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $article->getDoc()->saveXml($article->getDoc()->documentElement),
            $message
        );
    }

    public function replaceProvider() {
        return [
            [
                '<html><body><p>abc123</p></body></html>',
                $this->generate('<html><body><p><span class="test dropcap example">abc123</span></p></body></html>'),
                'span[class~=dropcap], span[class~=drop_cap]',
                'Clean text tags #1'
            ],
        ];
    }

    /**
     * @dataProvider replaceWithCallbackProvider
     */
    public function testReplaceWithCallback($expected, $article, $selector, $message)
    {
        $this->setDocument($article->getDoc());

        $this->call('replace', $selector, function($node) {
            return !$node->find('img')->count();
        });

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $article->getDoc()->saveXml($article->getDoc()->documentElement),
            $message
        );
    }

    public function replaceWithCallbackProvider() {
        return [
            [
                '<html><body><p>abcdefg</p></body></html>',
                $this->generate('<html><body><p><em>a</em><strong>b</strong><b>c</b><i>d</i><strike>e</strike><del>f</del><ins>g</ins></p></body></html>'),
                'em, strong, b, i, strike, del, ins',
                'Clean text tags #1'
            ],
            [
                '<html><body><p>a<em>b<img src="http://example.org/image.png" /></em></p></body></html>',
                $this->generate('<html><body><p><strong>a</strong><em>b<img src="http://example.org/image.png" /></em></p></body></html>'),
                'em, strong, b, i, strike, del, ins',
                'Clean text tags #2'
            ],
        ];
    }

    /**
     * @dataProvider removeBadTagsProvider
     */
    public function testRemoveBadTags($expected, $article, $message)
    {
        $this->setDocument($article->getDoc());

        $this->call('removeBadTags');

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $article->getDoc()->saveXml($article->getDoc()->documentElement),
            $message
        );
    }

    public function removeBadTagsProvider() {
        return [
            [
                '<html><body></body></html>',
                $this->generate('<html><body><a id="conditionalAd-test"></a></html>'),
                'Clean bad tags - id/class/name attribute starts with... #1'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><a class="publication"></a></html>'),
                'Clean bad tags - id/class/name attribute starts with... #2'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><input name="ad-test" /></html>'),
                'Clean bad tags - id/class/name attribute starts with... #3'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><a id="wp-caption-text></a></html>'),
                'Clean bad tags - id/class/name attribute contains... #1'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><a class="example-author></a></html>'),
                'Clean bad tags - id/class/name attribute contains... #2'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><input name="test-subscribe-example" /></html>'),
                'Clean bad tags - id/class/name attribute contains... #3'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><input name="subscribe-example" /></html>'),
                'Clean bad tags - id/class/name attribute contains... #4'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><a id="meta"></a></html>'),
                'Clean bad tags - id/class/name attribute ends with... #1'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><a class="test-meta"></a></html>'),
                'Clean bad tags - id/class/name attribute ends with... #2'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><input name="test-meta" /></html>'),
                'Clean bad tags - id/class/name attribute ends with... #3'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><input name="inset" /></html>'),
                'Clean bad tags - id/class/name attribute equals... #1'
            ],
        ];
    }

    /**
     * @dataProvider removeProvider
     */
    public function testRemove($expected, $article, $selector, $message)
    {
        $this->setDocument($article->getDoc());

        $this->call('remove', $selector);

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $article->getDoc()->saveXml($article->getDoc()->documentElement),
            $message
        );
    }

    public function removeProvider() {
        return [
            [
                '<html><body></body></html>',
                $this->generate('<html><body><span class="caption">Example</span></body></html>'),
                "[id='caption'],[class='caption']",
                'Remove nodes via filter #1'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><span class="test google filter">Example</span></body></html>'),
                "[id*=' google '],[class*=' google ']",
                'Remove nodes via filter #2'
            ],
            [
                '<html><body><p class="entry-more">Example</p></body></html>',
                $this->generate('<html><body><p class="entry-more">Example</p></body></html>'),
                "[id*='more']:not([id^=entry-]),[class*='more']:not([class^=entry-])",
                'Remove nodes via filter #3'
            ],
            [
                '<html><body></body></html>',
                $this->generate('<html><body><span class="something-more">Example</span></body></html>'),
                "[id*='more']:not([id^=entry-]),[class*='more']:not([class^=entry-])",
                'Remove nodes via filter #4'
            ],
        ];
    }

    /**
     * @dataProvider convertToParagraphProvider
     */
    public function testConvertToParagraph($expected, $article, $message)
    {
        $this->setDocument($article->getDoc());

        $this->call('convertToParagraph', 'div, span, article');

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $article->getDoc()->saveXml($article->getDoc()->documentElement),
            $message
        );
    }

    public function convertToParagraphProvider() {
        return [
            [
                '<html><body><div><img/><p>text</p><p>No children!</p><p>text</p></div></body></html>',
                $this->generate('<html><body><div><img/>text<p>No children!</p>text</div></body></html>'),
                'Convert wanted tags to paragraphs #1'
            ],
            [
                '<html><body><p attr="value">No children!</p></body></html>',
                $this->generate('<html><body><article attr="value">No children!</article></body></html>'),
                'Convert wanted tags to paragraphs #2'
            ],
            [
                '<html><body><div><img/><p><a grv-usedalready="yes">Example<img/></a>Text Node!</p><pre>Test!</pre></div></body></html>',
                $this->generate('<html><body><div><img/><a>Example<img/></a>Text Node!<pre>Test!</pre></div></body></html>'),
                'Convert wanted tags to paragraphs #3'
            ],
            [
                '<html><body><div><img/><p>Text Node!</p><pre>Test!</pre><a>Example<img/></a></div></body></html>',
                $this->generate('<html><body><div><img/>Text Node!<pre>Test!</pre><a>Example<img/></a></div></body></html>'),
                'Convert wanted tags to paragraphs #4'
            ],
            [
                '<html><body><div><img/><p><a grv-usedalready="yes">Example<img/></a>Text Node!</p><pre>Test!</pre><a>Example<img/></a></div></body></html>',
                $this->generate('<html><body><div><img/><a>Example<img/></a>Text Node!<pre>Test!</pre><a>Example<img/></a></div></body></html>'),
                'Convert wanted tags to paragraphs #5'
            ],
            [
                '<html><body><p><img/><a>Example<img/></a>Text Node!</p><pre>Test!</pre></body></html>',
                $this->generate('<html><body><p><img/><a>Example<img/></a>Text Node!</p><pre>Test!</pre></body></html>'),
                'Convert wanted tags to paragraphs #6'
            ],
            [
                '<html><body><div><img/><p><a grv-usedalready="yes">Example<img/></a>Text Node!</p><pre>Test!</pre><p>test<a grv-usedalready="yes">Example<img/></a></p></div></body></html>',
                $this->generate('<html><body><div><img/><a>Example<img/></a>Text Node!<pre>Test!</pre>test<a>Example<img/></a></div></body></html>'),
                'Convert wanted tags to paragraphs #7'
            ],
        ];
    }
}