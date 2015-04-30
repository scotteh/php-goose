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

        $this->article($article);

        $this->assertEquals(
            $expected,
            $this->call('getDateFromURL'),
            $message
        );
    }

    public function getDateFromURLProvider() {
        return [
            ['', 'http://example.org/', 'Stub']
        ];
    }
}