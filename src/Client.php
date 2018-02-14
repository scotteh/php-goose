<?php declare(strict_types=1);

namespace Goose;

/**
 * Client
 *
 * @package Goose
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Client {
    /** @var Configuration */
    protected $config;

    /**
     * @param mixed[] $config
     */
    public function __construct($config = []) {
        $this->config = new Configuration($config);
    }

    /**
     * @param string $name
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call(string $name, $arguments) {
        if (method_exists($this->config, $name)) {
            return call_user_func_array(array($this->config, $name), $arguments);
        }

        return null;
    }

    /**
     * @param string $url
     * @param string $rawHTML
     *
     * @return Article
     */
    public function extractContent(string $url, string $rawHTML = null): ?Article {
        $crawler = new Crawler($this->config);
        $article = $crawler->crawl($url, $rawHTML);

        return $article;
    }
}