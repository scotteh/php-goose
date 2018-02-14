<?php declare(strict_types=1);

namespace Goose\Modules;

use Goose\Configuration;

/**
 * Abstract Formatter
 *
 * @package Goose\Modules
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
abstract class AbstractModule {
    /** @var Configuration */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @return Configuration
     */
    public function config(): Configuration {
        return $this->config;
    }
}
