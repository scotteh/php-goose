<?php

namespace Goose\Tests\Harness;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;

/**
 * Test Trait
 *
 * @package Goose\Tests\Harness
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait TestTrait {
    /** @var Article */
    private $article;

    /** @var DOMDocument */
    private $document;

    /**
     * @param DOMDocument|DOMElement|Article $doc
     *
     * @return string
     */
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

    /**
     * @param string $html
     *
     * @return DOMDocument
     */
    private function document($html) {
        $doc = new DOMDocument();
        $doc->html($html);

        return $doc;
    }

    /**
     * @param string $html
     *
     * @return Article
     */
    private function generate($html) {
        $article = new Article();
        $article->setDoc($this->document($html));

        return $article;
    }

    /**
     * @return Configuration
     */
    private function config() {
        $config = new Configuration();

        return $config;
    }

    /**
     * @param Article $article
     *
     * @return Article
     */
    private function setArticle(Article $article = null) {
        if ($article === null) {
            return $this->article;
        }

        $this->article = $article;
    }

    /**
     * @param DOMDocument $document
     *
     * @return DOMDocument
     */
    private function setDocument(DOMDocument $document) {
        $this->document = $document;
    }

    /**
     * @param string $method
     * @param mixed $arguments,...
     *
     * @return mixed
     */
    private function call($method) {
        $arguments = func_get_args();

        array_shift($arguments);

        $obj = new self::$CLASS_NAME($this->config());

        $class = new \ReflectionClass(self::$CLASS_NAME);

        if ($class->hasProperty('article')) {
            $prop = $class->getProperty('article');
            $prop->setAccessible(true);
            $prop->setValue($obj, $this->article);
        }

        if ($class->hasProperty('document')) {
            $prop = $class->getProperty('document');
            $prop->setAccessible(true);
            $prop->setValue($obj, $this->document);
        }

        if (!$class->hasMethod($method)) {
            throw new BadMethodCallException();
        }

        $fn = $class->getMethod($method);
        $fn->setAccessible(true);
        $result = $fn->invokeArgs($obj, $arguments);

        return $result;
    }
}