<?php

namespace Goose\Tests\Modules\Extractors;

use Goose\Article;
use Goose\Configuration;
use Goose\Modules\Extractors\MetaExtractor;

class MetaExtractorTest extends \PHPUnit_Framework_TestCase
{
    use \Goose\Tests\Harness\TestTrait;

    private static $CLASS_NAME = '\Goose\Modules\Extractors\MetaExtractor';

    /**
     * @dataProvider getTitleProvider
     */
    public function testGetTitle($expected, $article, $message)
    {
        $article->setOpenGraph([
            'site_name' => 'Example Website',
        ]);
        $article->setDomain('www.example.com');

        $this->setArticle($article);

        $this->assertSame(
            $expected,
            $this->call('getTitle'),
            $message
        );
    }

    public function testEmptyMetaLanguageShouldNotRewriteConfiguredValue()
    {
        $article = $this->generate('<html><head><title>Ut venenatis rutrum ex, eu feugiat dolor</title></head></html>');
        $metaExtractor = new MetaExtractor(new Configuration([
            'language' => 'zh'
        ]));
        $metaExtractor->run($article);
        $this->assertSame(
            'zh',
            $metaExtractor->config()->get('language')
        );
    }

    public function testMetaLanguageShouldRewriteConfiguredValue()
    {
        $article = $this->generate('<html><head><title>Ut venenatis rutrum ex, eu feugiat dolor</title><meta name="lang" content="ru" /></head></html>');
        $metaExtractor = new MetaExtractor(new Configuration([
            'language' => 'zh'
        ]));
        $metaExtractor->run($article);
        $this->assertSame(
            'ru',
            $metaExtractor->config()->get('language')
        );
    }

    public function getTitleProvider() {
        return [
            ['Ut venenatis rutrum ex, eu feugiat dolor', $this->generate('<html><head><title>Ut venenatis rutrum ex, eu feugiat dolor</title></head></html>'), 'No splitter'],
            ['Ut venenatis | rutrum ex, eu feugiat dolor', $this->generate('<html><head><title>Ut venenatis | rutrum ex, eu feugiat dolor | Example Website</title></head></html>'), 'Pipe splitter'],
            ['Ut venenatis - rutrum ex, eu feugiat dolor', $this->generate('<html><head><title>Ut venenatis - rutrum ex, eu feugiat dolor - www.example.com</title></head></html>'), 'Dash splitter'],
            ['Ut venenatis : rutrum ex, eu feugiat dolor', $this->generate('<html><head><title>Ut venenatis : rutrum ex, eu feugiat dolor : www.example.com </title></head></html>'), 'Colon splitter'],
            // libxml will automatically place <title> inside <head>.
            ['Ut venenatis rutrum ex, eu feugiat dolor', $this->generate('<html><title>Ut venenatis rutrum ex, eu feugiat dolor</title></html>'), 'Title tag not in head tag'],
            ['', $this->generate('<html></html>'), 'No title tag'],
            ['Ut venenatis rutrum ex, eu feugiat dolor', $this->generate('<html><head><title>Ut venenatis rutrum ex, eu feugiat dolor |</title></head></html>'), 'Splitter as last character'],
            ['|Ut venenatis rutrum ex, eu feugiat dolor', $this->generate('<html><head><title>|Ut venenatis rutrum ex, eu feugiat dolor</title></head></html>'), 'Splitter as first character'],
            ['', $this->generate('<html><head><title>|</title></head></html>'), 'Splitter as only character'],
        ];
    }
}