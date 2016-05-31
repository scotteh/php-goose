<?php

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\AbstractModule;
use Goose\Modules\ModuleInterface;
use DOMWrap\Element;

/**
 * Publish Date Extractor
 *
 * @package Goose\Modules\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class PublishDateExtractor extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait;

    /**
     * @param Article $article
     *
     * @return DateTime
     */
    public function run(Article $article) {
        $this->article($article);

        $article->setPublishDate($this->getDateFromURL());
    }

    private function getDateFromURL() {
        // Determine date based on URL
        if (preg_match('@(?:[\d]{4})(?<delimiter>[/-])(?:[\d]{2})\k<delimiter>(?:[\d]{2})@U', $this->article()->getFinalUrl(), $matches)) {
            $dt = \DateTime::createFromFormat('Y' . $matches['delimiter'] . 'm' . $matches['delimiter'] . 'd', $matches[0]);
            $dt->setTime(0, 0, 0);

            if ($dt === false) {
                return null;
            }

            return $dt;
        }

        /** @todo Add more date detection methods */

        return null;
    }

    /**
     * Check for and determine dates from Schema.org's datePublished property.
     *
     * Checks HTML tags (e.g. <meta>, <time>, etc.) and JSON-LD.
     *
     * @return \DateTime|null
     *
     * @see https://schema.org/datePublished
     */
    private function getDateFromSchemaOrg() {
        $dt = null;

        // Check for HTML tags (<meta>, <time>, etc.)
        $nodes = $this->article()->getRawDoc()->find('*[itemprop="datePublished"]');

        /* @var $node Element */
        foreach ($nodes as $node) {
            try {
                if ($node->hasAttribute('datetime')) {
                    $dt = new \DateTime($node->getAttribute('datetime'));
                    break;
                }
                if ($node->hasAttribute('content')) {
                    $dt = new \DateTime($node->getAttribute('content'));
                    break;
                }
            }
            catch (\Exception $e) {
                // Do nothing here in case the node has unrecognizable date information.
            }
        }

        if (!is_null($dt)) {
            return $dt;
        }

        // Check for JSON-LD
        $nodes = $this->article()->getRawDoc()->find('script[type="application/ld+json"]');

        /* @var $node Element */
        foreach ($nodes as $node) {
            try {
                $json = json_decode($node->text());
                if (isset($json->datePublished)) {
                    $dt = new \DateTime($json->datePublished);
                    break;
                }
            }
            catch (\Exception $e) {
                // Do nothing here in case the node has unrecognizable date information.
            }
        }

        return $dt;
    }
}
