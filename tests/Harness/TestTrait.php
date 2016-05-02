<?php

namespace Goose\Tests\Harness;

use Goose\Article;
use Goose\Configuration;
use DOMWrap\Element;
use DOMWrap\Document;

/**
 * Test Trait
 *
 * @package Goose\Tests\Harness
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
trait TestTrait {
    /** @var Article */
    private $article;

    /** @var Document */
    private $document;

    /**
     * @param Document|Element|Article $doc
     *
     * @return string
     */
    public function html($doc) {
        if ($doc instanceof Document) {
            $el = $doc->documentElement;
        } else if ($doc instanceof Element) {
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
     * @return Document
     */
    private function document($html) {
        $doc = new Document();
        $doc->html($html);

        // Remove the doctype (if it exists) so we can use Document::$firstChild
        if ($doc->doctype instanceof \DOMDocumentType) {
            $doc->removeChild($doc->doctype);
        }

        // Remove any leading ProcessingInstructions
        if ($doc->firstChild instanceof \DOMProcessingInstruction) {
            $doc->removeChild($doc->firstChild);
        }

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
     * @param Document $document
     *
     * @return Document
     */
    private function setDocument(Document $document) {
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