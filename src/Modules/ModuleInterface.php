<?php declare(strict_types=1);

namespace Goose\Modules;

use Goose\{Article, Configuration};

/**
 * Module Interface
 *
 * @package Goose\Modules
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
interface ModuleInterface {
    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config);

    /**
     * @param Article $article
     *
     * @return self
     */
    public function run(Article $article);
}
