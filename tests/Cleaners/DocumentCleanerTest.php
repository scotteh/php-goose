<?php

namespace Goose\Tests\Extractors;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;
use Goose\Cleaners\DocumentCleaner;

class DocumentCleanerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider removeCommentsProvider
     */
    public function testRemoveComments($expected, $article, $message)
    {
        $obj = new DocumentCleaner(null);

        $this->assertEquals(
            $this->html($expected),
            $this->html($obj->clean($article)),
            $message
        );
    }

    public function removeCommentsProvider() {
        return [
            [
                $this->document('<html></html>'),
                $this->generate('<html><!-- Comment --></html>'),
                'Single Line Comment'
            ],
            [
                $this->document('<html></html>'),
                $this->generate("<html><!-- \n Comment \n --></html>"),
                'Multi Line Comment'
            ],
        ];
    }

    /**
     * @dataProvider cleanTextTagsProvider
     */
    public function testCleanTextTags($expected, $article, $message)
    {
        $obj = new DocumentCleaner(null);

        $this->assertEquals(
            $this->html($expected),
            $this->html($obj->clean($article)),
            $message
        );
    }

    public function cleanTextTagsProvider() {
        return [
            [
                $this->document('<html><p>a b c d e f g </p></html>'),
                $this->generate('<html><p><em>a</em><strong>b</strong><b>c</b><i>d</i><strike>e</strike><del>f</del><ins>g</ins></p></html>'),
                'Clean text tags #1'
            ],
            [
                $this->document('<html><p>a <em>b<img src="http://example.org/image.png" /></em></p></html>'),
                $this->generate('<html><p><strong>a</strong><em>b<img src="http://example.org/image.png" /></em></p></html>'),
                'Clean text tags #2'
            ],
        ];
    }

    /**
     * @dataProvider cleanUpSpanTagsInParagraphsProvider
     */
    public function testCleanUpSpanTagsInParagraphs($expected, $article, $message)
    {
        $obj = new DocumentCleaner(null);

        $this->assertEquals(
            $this->html($expected),
            $this->html($obj->clean($article)),
            $message
        );
    }

    public function cleanUpSpanTagsInParagraphsProvider() {
        return [
            [
                $this->document('<html><p>Example</p></html>'),
                $this->generate('<html><p><span>Example</span></p></html>'),
                'Replace single span tag'
            ],
            [
                $this->document('<html><p>Example Tags</p></html>'),
                $this->generate('<html><p><span>Example</span> <span>Tags</span></p></html>'),
                'Replace multiple span tags'
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
        $doc->loadHTML($html);

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