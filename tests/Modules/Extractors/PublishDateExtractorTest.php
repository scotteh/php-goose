<?php

namespace Goose\Tests\Modules\Extractors;

use Goose\Article;

class PublishDateExtractorTest extends \PHPUnit_Framework_TestCase
{
    use \Goose\Tests\Harness\TestTrait;

    private static $CLASS_NAME = '\Goose\Modules\Extractors\PublishDateExtractor';

    /**
     * @dataProvider getDateFromURLProvider
     */
    public function testGetDateFromURL($expected, $url, $message)
    {
        $article = new Article();
        $article->setFinalUrl($url);

        $this->setArticle($article);

        $this->assertEquals(
            $expected,
            $this->call('getDateFromURL'),
            $message
        );
    }

    public function getDateFromURLProvider() {
        return [
            [new \DateTime('2014-03-26'), 'http://example.org/2014/03/26/hello-world', 'Date format: Y/m/d'],
            [new \DateTime('2014-03-26'), 'http://example.org/2014-03-26/hello-world', 'Date format: Y-m-d'],
            [null, 'http://example.org/folder/2014-203-2a6/hello-world', 'Date format: Invalid #1'],
            [null, 'http://example.org/folder/2014-03/26/hello-world', 'Date format: Invalid #2'],
        ];
    }

    /**
     * @dataProvider getDateFromSchemaOrgProvider
     */
    public function testGetDateFromSchemaOrg($expected, $document, $message)
    {
        $article = $this->generate($document);
        $this->setArticle($article);
        $article->setRawDoc($document);

        $this->assertEquals(
            $expected,
            $this->call('getDateFromSchemaOrg'),
            $message
        );
    }

    public function getDateFromSchemaOrgProvider() {
        return [
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta itemprop="datePublished" content="2016-05-31T22:52:11Z"></head></html>'),
                'Valid date with tag: <meta> and attribute: "content"'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta itemprop="datePublished" datetime="2016-05-31T22:52:11Z"></head></html>'),
                'Valid date with tag: <meta> and attribute: "datetime"'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title></head><body><article>Content<time itemprop="datePublished" datetime="2016-05-31T22:52:11Z"></article></body></html>'),
                'Valid date with tag: <time> and attribute: "datetime"'
            ],
            [
                new \DateTime('2016-05-31'),
                $this->document('<html><head><title>Example Article</title><script type="application/ld+json">{"@context":"http://schema.org","@type":"Article","author":"John Smith","datePublished":"2016-05-31"}</script></head></html>'),
                'Valid date with JSON-LD and attribute: "datePublished"'
            ],
            [
                null,
                $this->document('<html><head><title>Example Article</title></head></html>'),
                'No date provided'
            ],
            [
                null,
                $this->document('<html><head><title>Example Article</title><meta itemprop="datePublished" datetime="two days ago"></head></html>'),
                'Invalid date format provided'
            ]
        ];
    }

    /**
     * @dataProvider getDateFromDublinCoreProvider
     */
    public function testGetDateFromDublinCore($expected, $document, $message)
    {
        $article = $this->generate($document);
        $this->setArticle($article);
        $article->setRawDoc($document);

        $this->assertEquals(
            $expected,
            $this->call('getDateFromDublinCore'),
            $message
        );
    }

    public function getDateFromDublinCoreProvider() {
        return [
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta name="dc.date" content="2016-05-31T22:52:11Z"></head></html>'),
                'Valid date with tag: <meta> and name: "dc.date"'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta name="dc.date.issued" content="2016-05-31T22:52:11Z"></head></html>'),
                'Valid date with tag: <meta> and name: "dc.date.issued"'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta name="DC.date.issued" content="2016-05-31T22:52:11Z"></head></html>'),
                'Valid date with tag: <meta> and name: "DC.date.issued"'
            ],
            [
                null,
                $this->document('<html><head><title>Example Article</title></head></html>'),
                'No date provided'
            ],
            [
                null,
                $this->document('<html><head><title>Example Article</title><meta name="DC.date.issued" content="2"</head></html>'),
                'Invalid date format provided'
            ]
        ];
    }

    /**
     * @dataProvider getDateFromOpenGraphProvider
     */
    public function testGetDateFromOpenGraph($expected, $article, $message)
    {
        $this->setArticle($article);

        $this->assertEquals(
            $expected,
            $this->call('getDateFromOpenGraph'),
            $message
        );
    }

    public function getDateFromOpenGraphProvider() {
        $pubdate_article = $this->generate('<html><head><title>Example Article</title></html>');
        $pubdate_article->setOpenGraph(['pubdate' => '2016-05-31T22:52:11Z']);

        $published_time_article = $this->generate('<html><head><title>Example Article</title></html>');
        $published_time_article->setOpenGraph(['published_time' => '2016-05-31T22:52:11Z']);

        $no_og_article = $this->generate('<html><head><title>Example Article</title></html>');

        return [
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $pubdate_article,
                'Valid date with og:pubdate'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $published_time_article,
                'Valid date with og:article and article:published_time'
            ],
            [
                null,
                $no_og_article,
                'No date provided'
            ]
        ];
    }


    /**
     * @dataProvider getDateFromParselyProvider
     */
    public function testGetDateFromParsely($expected, $document, $message)
    {
        $article = $this->generate($document);
        $this->setArticle($article);
        $article->setRawDoc($document);

        $this->assertEquals(
            $expected,
            $this->call('getDateFromParsely'),
            $message
        );
    }

    public function getDateFromParselyProvider() {
        return [
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><script type="application/ld+json">{"@context":"http://schema.org","@type":"NewsArticle","creator":["John Smith"],"dateCreated":"2016-05-31T22:52:11Z"}</script></head></html>'),
                'Valid date with JSON-LD and attribute: "datePublished"'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta name="parsely-pub-date" content="2016-05-31T22:52:11Z"></head></html>'),
                'Valid date with tag: <meta> and name: "parsely-pub-date"'
            ],
            [
                new \DateTime('2016-05-31T22:52:11Z'),
                $this->document('<html><head><title>Example Article</title><meta name="parsely-page" content=\'{"title": "Example Article","pub_date": "2016-05-31T22:52:11Z"}\'></head></html>'),
                'Valid date with tag: <meta>, name: "parsely-page", attribute "content" and JSON parameter "pub_date"'
            ],
            [
                null,
                $this->document('<html><head><title>Example Article</title></head></html>'),
                'No date provided'
            ],
            [
                null,
                $this->document('<html><head><title>Example Article</title><meta name="parsely-pub-date" content="last"></head></html>'),
                'Invalid date provided'
            ],
        ];
    }
}