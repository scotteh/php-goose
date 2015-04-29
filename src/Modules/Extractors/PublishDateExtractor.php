<?php

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Modules\AbstractModule;
use Goose\Modules\ModuleInterface;

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
}
