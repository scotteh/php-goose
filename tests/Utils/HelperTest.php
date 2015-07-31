<?php

namespace Goose\Tests\Utils;

use Goose\Utils\Helper;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getCleanedUrlProvider
     */
    public function testGetCleanedUrl($expected, $url, $message)
    {
        $this->assertEquals(
            $expected,
            Helper::getCleanedUrl($url),
            $message
        );
    }

    public function getCleanedUrlProvider() {
        return [
            [
                (object)[
                    'url' => 'http://user:pass@example.org:80/dir/file.html?key1=value1&key2=value2#!fragment',
                    'parts' => (object)[
                        'scheme' => 'http',
                        'host' => 'example.org',
                        'port' => 80,
                        'user' => 'user',
                        'pass' => 'pass',
                        'path' => '/dir/file.html',
                        'query' => 'key1=value1&key2=value2',
                        'fragment' => '!fragment',
                    ],
                    'linkhash' => md5('http://user:pass@example.org:80/dir/file.html?key1=value1&key2=value2#!fragment'),
                    'finalUrl' => 'http://user:pass@example.org:80/dir/file.html?key1=value1&key2=value2&_escaped_fragment_=fragment',
                ],
                'http://user:pass@example.org:80/dir/file.html?key1=value1&key2=value2#!fragment',
                'Complete URL #1'
            ],
            [
                (object)[
                    'url' => 'http://example.org/file.html#!fragment',
                    'parts' => (object)[
                        'scheme' => 'http',
                        'host' => 'example.org',
                        'path' => '/file.html',
                        'fragment' => '!fragment',
                    ],
                    'linkhash' => md5('http://example.org/file.html#!fragment'),
                    'finalUrl' => 'http://example.org/file.html?_escaped_fragment_=fragment',
                ],
                'http://example.org/file.html#!fragment',
                'Complete URL #2'
            ],
        ];
    }
    /**
     * @expectedException Goose\Exceptions\MalformedURLException
     */
    public function testGetCleanedUrlException()
    {
        Helper::getCleanedUrl('http://example.org:port/');
    }
}