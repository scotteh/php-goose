<?php

namespace Goose\Tests\Modules\Extractors;

use Goose\Article;

class AdditionalDataExtractorTest extends \PHPUnit\Framework\TestCase
{
    use \Goose\Tests\Harness\TestTrait;

    private static $CLASS_NAME = '\Goose\Modules\Extractors\AdditionalDataExtractor';

    /**
     * @dataProvider getGetVideos
     */
    public function testGetVideos($expected, $document, $message)
    {
        $article = $this->generate($document);
        $this->setArticle($article);
        $article->setRawDoc($document);
        $article->setTopNode($document->find('div')->first());

        $this->assertEquals(
            $expected,
            $this->call('getVideos'),
            $message
        );
    }

    public function getGetVideos() {
        return [
            [
                ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
                $this->document('<html><head><title>Example Article</title></head><body><div><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe></div></body></html>'),
                'Extract video by domain match'
            ],
            [
                ['/media/video1.mpg'],
                $this->document('<html><head><title>Example Article</title></head><body><div><video src="/media/video1.mpg"></video></div></body></html>'),
                'Extract video by file type match'
            ],
        ];
    }
}