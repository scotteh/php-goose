<?php declare(strict_types=1);

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\{AbstractModule, ModuleInterface};
use DOMWrap\Element;

/**
 * Publish Date Extractor
 *
 * @package Goose\Modules\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class PublishDateExtractor extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait;

    /** @inheritdoc  */
    public function run(Article $article): self {
        $this->article($article);

        $dt = $this->getDateFromSchemaOrg();

        if (is_null($dt)) {
            $dt = $this->getDateFromOpenGraph();
        }

        if (is_null($dt)) {
            $dt = $this->getDateFromURL();
        }

        if (is_null($dt)) {
            $dt = $this->getDateFromDublinCore();
        }

        if (is_null($dt)) {
            $dt = $this->getDateFromParsely();
        }

        $article->setPublishDate($dt);

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    private function getDateFromURL(): ?\DateTime {
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
    private function getDateFromSchemaOrg(): ?\DateTime {
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
        foreach ( $nodes as $node )
        {
            try {
                $json = json_decode( $node->text() );
                if ( isset( $json->datePublished ) && is_string( $json->datePublished ) )
                {
                    $dt = new \DateTime( $json->datePublished );
                    break;
                }
            }
            catch (\Exception $e) {
                // Do nothing here in case the node has unrecognizable date information.
            }
        }

        return $dt;
    }

    /**
     * Check for and determine dates based on Dublin Core standards.
     *
     * @return \DateTime|null
     *
     * @see http://dublincore.org/documents/dcmi-terms/#elements-date
     * @see http://dublincore.org/documents/2000/07/16/usageguide/qualified-html.shtml
     */
    private function getDateFromDublinCore(): ?\DateTime {
        $dt = null;
        $nodes = $this->article()->getRawDoc()->find('*[name="dc.date"], *[name="dc.date.issued"], *[name="DC.date.issued"]');

        /* @var $node Element */
        foreach ($nodes as $node) {
            try {
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

        return $dt;
    }

    /**
     * Check for and determine dates based on OpenGraph standards.
     *
     * @return \DateTime|null
     *
     * @see http://ogp.me/
     * @see http://ogp.me/#type_article
     */
    private function getDateFromOpenGraph(): ?\DateTime {
        $dt = null;

        $og_data = $this->article()->getOpenGraph();

        try {
            if (isset($og_data['published_time'])) {
                $dt = new \DateTime($og_data['published_time']);
            }
            if (is_null($dt) && isset($og_data['pubdate'])) {
                $dt = new \DateTime($og_data['pubdate']);
            }
        }
        catch (\Exception $e) {
            // Do nothing here in case the node has unrecognizable date information.
        }

        return $dt;
    }

    /**
     * Check for and determine dates based on Parsely metadata.
     *
     * Checks JSON-LD, <meta> tags and parsely-page.
     *
     * @return \DateTime|null
     *
     * @see https://www.parsely.com/help/integration/jsonld/
     * @see https://www.parsely.com/help/integration/metatags/
     * @see https://www.parsely.com/help/integration/ppage/
     */
    private function getDateFromParsely(): ?\DateTime {
        $dt = null;

        // JSON-LD
        $nodes = $this->article()->getRawDoc()->find('script[type="application/ld+json"]');

        /* @var $node Element */
        foreach ($nodes as $node) {
            try {
                $json = json_decode($node->text());
                if (isset($json->dateCreated)) {
                    $dt = new \DateTime($json->dateCreated);
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

        // <meta> tags
        $nodes = $this->article()->getRawDoc()->find('meta[name="parsely-pub-date"]');

        /* @var $node Element */
        foreach ($nodes as $node) {
            try {
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

        // parsely-page
        $nodes = $this->article()->getRawDoc()->find('meta[name="parsely-page"]');

        /* @var $node Element */
        foreach ($nodes as $node) {
            try {
                if ($node->hasAttribute('content')) {
                    $json = json_decode($node->getAttribute('content'));
                    if (isset($json->pub_date)) {
                        $dt = new \DateTime($json->pub_date);
                        break;
                    }
                }
            }
            catch (\Exception $e) {
                // Do nothing here in case the node has unrecognizable date information.
            }
        }

        return $dt;
    }
}
