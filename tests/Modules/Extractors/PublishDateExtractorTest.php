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
}