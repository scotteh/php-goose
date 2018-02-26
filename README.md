# PHP Goose - Article Extractor
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/scotteh/php-goose/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/scotteh/php-goose/?branch=master) [![Build Status](https://travis-ci.org/scotteh/php-goose.svg?branch=master)](https://travis-ci.org/scotteh/php-goose)

## Intro

This projects mission is to take any news article or article type web page and not only detect and extract what is the main body of the article, but also the most probable feature image candidates and all other metadata/images/videos.

Goose will try to extract the following information:

 - Main text of an article
 - Main image of article
 - Any YouTube/Vimeo movies embedded in article
 - Meta description
 - Meta tags
 - Publish date

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

This library is designed to be installed via [Composer](https://getcomposer.org/doc/).

Install composer
``` bash
curl -sS https://getcomposer.org/installer | php
```

Next, install the latest stable version of php-goose.
``` bash
php composer.phar require scotteh/php-goose ^1.0
```

This library requires an autoloader, if you aren't already using one you can include [Composers autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading).
``` php
require('vendor/autoload.php');
```

To update php-goose later you can run the following command:
``` bash
php composer.phar update
```

## Configuration

All config options are not required and are optional. Default (fallback) values have been used below.

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

PHP Goose is a port of [Goose](https://github.com/GravityLabs/goose/) originally developed in Java and converted to Scala by [GravityLabs](https://github.com/GravityLabs/). Portions have also been ported from the Python port [python-goose](https://github.com/grangier/python-goose).

This version was written by:

 - [Andrew Scott](https://github.com/scotteh)

## Licensing

PHP Goose is licensed by Gravity.com under the Apache 2.0 license, see the LICENSE file for more details.
