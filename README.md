# PHP Goose - Article Extractor
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/scotteh/php-goose/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/scotteh/php-goose/?branch=master) [![Build Status](https://travis-ci.org/scotteh/php-goose.svg?branch=master)](https://travis-ci.org/scotteh/php-goose)

PHP Goose is designed to detect and extract the main body of articles, associated media and metadata from a news or article type web page.

Types of data extracted:

 - Main text of an article
 - Main feature image of an article
 - Other featured images
 - YouTube/Vimeo movies embedded in article
 - Meta description
 - Meta tags
 - Publish date
 - Popular words

``` php
use Goose\Client as GooseClient;

$goose = new GooseClient();
$article = $goose->extractContent('http://url.to/article');

$title = $article->getTitle();
$metaDescription = $article->getMetaDescription();
$metaKeywords = $article->getMetaKeywords();
$canonicalLink = $article->getCanonicalLink();
$domain = $article->getDomain();
$tags = $article->getTags();
$links = $article->getLinks();
$videos = $article->getVideos();
$articleText = $article->getCleanedArticleText();
$entities = $article->getPopularWords();
$image = $article->getTopImage();
$allImages = $article->getAllImages();
```

## Requirements

 - PHP 7.1 or later
 - PSR-4 compatible autoloader
 
The older 0.x versions with PHP 5.5+ support are still available under [releases](https://github.com/scotteh/php-goose/releases).

## Install

PHP Goose is designed to be installed via [Composer](https://getcomposer.org/doc/).

Download composer:
``` bash
curl -sS https://getcomposer.org/installer | php
```

Next, install the latest stable version of PHP Goose:
``` bash
php composer.phar require scotteh/php-goose ^1.0
```

Once installed, require [Composer's autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading).
``` php
require('vendor/autoload.php');
```

To update PHP Goose later you can run the following command:
``` bash
php composer.phar update
```

## Usage

### Fetching Articles

### Extracting Locally

### Media

### Configuration

All configuration options are optional. Default values are listed below.

``` php
$goose = new GooseClient([
    // Language - Selects common word dictionary
    //   Supported languages (ISO 639-1):
    //     ar, cs, da, de, en, es, fi, fr, hu, id, it, ja,
    //     ko, nb, nl, no, pl, pt, ru, sv, zh
    'language' => 'en',
    // Minimum image size (bytes)
    'image_min_bytes' => 4500,
    // Maximum image size (bytes)
    'image_max_bytes' => 5242880,
    // Minimum image size (pixels)
    'image_min_width' => 120,
    // Maximum image size (pixels)
    'image_min_height' => 120,
    // Fetch best image
    'image_fetch_best' => true,
    // Fetch all images
    'image_fetch_all' => false,
    // Guzzle configuration - All values are passed directly to Guzzle
    //   See: http://guzzle.readthedocs.io/en/stable/request-options.html
    'browser' => [
        'timeout' => 60,
        'connect_timeout' => 30
    ]
]);
```

## About

PHP Goose has been written by:
 - [Andrew Scott](https://github.com/scotteh)

This library is a port of [Goose](https://github.com/GravityLabs/goose/) originally developed in Java and converted to Scala by [GravityLabs](https://github.com/GravityLabs/). Portions have also been ported from the Python port [python-goose](https://github.com/grangier/python-goose). 

## Licensing

PHP Goose is licensed by Gravity.com under the Apache 2.0 license, see the LICENSE file for more details.
