<?php

namespace Goose;

use Goose\Text\StopWords;
use Goose\Modules\ModuleInterface;

/**
 * Configuration
 *
 * @package Goose
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Configuration {
    /** @var mixed[] */
    protected $options = [
        'language' => 'en',
        'image_min_bytes' => 4500,
        'image_max_bytes' => 5242880,
        'image_min_width' => 120,
        'image_min_height' => 120,
        'image_fetch_best' => true,
        'image_fetch_all' => false,
        /** @see http://guzzle.readthedocs.org/en/latest/clients.html#request-options */
        'browser' => [
            'timeout' => 60,
            'connect_timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36',
                'Referer' => 'https://www.google.com/',
            ],
        ]
    ];

    /** @var mixed[] */
    protected $modules = [
        'cleaners' => [
            '\Goose\Modules\Cleaners\DocumentCleaner',
        ],
        'extractors' => [
            '\Goose\Modules\Extractors\MetaExtractor',
            '\Goose\Modules\Extractors\ContentExtractor',
            '\Goose\Modules\Extractors\ImageExtractor',
            '\Goose\Modules\Extractors\PublishDateExtractor',
            '\Goose\Modules\Extractors\AdditionalDataExtractor',
        ],
        'formatters' => [
            '\Goose\Modules\Formatters\OutputFormatter',
        ],
    ];

    /**
     * @param mixed[] $options
     */
    public function __construct($options = []) {
        if (is_array($options)) {
            $this->options = array_replace_recursive($this->options, $options);
        }
    }

    /**
     * @param string $option
     *
     * @return mixed
     */
    public function get($option) {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return null;
    }

    /**
     * @param string $option
     * @param mixed $value
     */
    public function set($option, $value) {
        $this->options[$option] = $value;
    }

    /**
     * @param string $category
     *
     * @return mixed
     */
    public function getModules($category) {
        if (isset($this->modules[$category])) {
            return $this->modules[$category];
        }

        return null;
    }

    /**
     * @param string $category
     * @param string[] $classes
     */
    public function setModules($category, $classes) {
        if ($this->areValidModules($category, $classes)) {
            $this->modules[$category] = $classes;
        }
    }

    /**
     * @param string $category
     * @param string $class
     */
    public function addModule($category, $class) {
        if ($this->isValidModule($category, $class)) {
            $this->modules[$category][] = $class;
        }
    }

    /**
     * @param string $category
     * @param string $class
     */
    public function removeModule($category, $class) {
        if (isset($this->modules[$category])) {
            $key = array_search($class, $this->modules[$category]);

            if ($key !== false) {
                unset($this->modules[$category][$key]);
            }
        }
    }

    /**
     * @param string $category
     * @param string $class
     *
     * @return bool
     */
    public function isValidModule($category, $class) {
        if (isset($this->modules[$category])
          && $class instanceof ModuleInterface) {
            return true;
        }

        return false;
    }

    /**
     * @param string $category
     * @param string[] $classes
     *
     * @return bool
     */
    public function areValidModules($category, $classes) {
        if (is_array($classes)) {
            foreach ($classes as $class) {
                if (!$this->isValidModule($category, $class)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @var StopWords|null */
    protected $stopWords;

    /*
     * @return StopWords
     */
    public function getStopWords() {
        if (is_null($this->stopWords)) {
            $this->stopWords = new StopWords($this);
        }

        return $this->stopWords;
    }

    /**
     * @param StopWords|null $stopWords
     */
    public function setStopWords(StopWords $stopWords = null) {
        $this->stopWords = $stopWords;
    }
}
