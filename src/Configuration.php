<?php declare(strict_types=1);

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
        /** @see http://guzzle.readthedocs.io/en/stable/request-options.html */
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
    public function __construct(array $options = []) {
        if (is_array($options)) {
            $this->options = array_replace_recursive($this->options, $options);
        }
    }

    /**
     * @param string $option
     *
     * @return mixed
     */
    public function get(string $option) {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return null;
    }

    /**
     * @param string $option
     * @param mixed $value
     *
     * @return self
     */
    public function set(string $option, $value): self {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * @param string $category
     *
     * @return mixed
     */
    public function getModules(string $category) {
        if (isset($this->modules[$category])) {
            return $this->modules[$category];
        }

        return null;
    }

    /**
     * @param string $category
     * @param string[] $classes
     *
     * @return self
     */
    public function setModules(string $category, array $classes): self {
        if ($this->areValidModules($category, $classes)) {
            $this->modules[$category] = $classes;
        }

        return $this;
    }

    /**
     * @param string $category
     * @param string $class
     *
     * @return self
     */
    public function addModule(string $category, string $class): self {
        if ($this->isValidModule($category, $class)) {
            $this->modules[$category][] = $class;
        }

        return $this;
    }

    /**
     * @param string $category
     * @param string $class
     *
     * @return self
     */
    public function removeModule(string $category, string $class): self {
        if (isset($this->modules[$category])) {
            $key = array_search($class, $this->modules[$category]);

            if ($key !== false) {
                unset($this->modules[$category][$key]);
            }
        }

        return $this;
    }

    /**
     * @param string $category
     * @param string $class
     *
     * @return bool
     */
    public function isValidModule(string $category, string $class): bool {
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
    public function areValidModules(string $category, array $classes): bool {
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

    /**
     * @param StopWords|null $stopWords
     *
     * @return self
     */
    public function setStopWords(StopWords $stopWords = null): ?self {
        $this->stopWords = $stopWords;

        return $this;
    }

    /*
     * @return StopWords
     */
    public function getStopWords(): StopWords {
        if (is_null($this->stopWords)) {
            $this->stopWords = new StopWords($this);
        }

        return $this->stopWords;
    }
}
