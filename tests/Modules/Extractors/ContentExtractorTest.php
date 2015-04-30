<?php

namespace Goose\Tests\Modules\Extractors;

use Goose\Article;

class ContentExtractorTest extends \PHPUnit_Framework_TestCase
{
    use \Goose\Tests\Harness\TestTrait;

    private static $CLASS_NAME = '\Goose\Modules\Extractors\ContentExtractor';

    /**
     * @dataProvider calculateBestNodeBasedOnClusteringProvider
     */
    public function testCalculateBestNodeBasedOnClustering($expected, $article, $message)
    {
        // TODO
    }

    public function calculateBestNodeBasedOnClusteringProvider() {
        return [
        ];
    }
}