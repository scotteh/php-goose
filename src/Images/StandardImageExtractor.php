<?php

namespace Goose\Images;

use Goose\Article;
use Goose\Configuration;
use Goose\DOM\DOMElement;
use Goose\DOM\DOMDocument;
use Goose\DOM\DOMNodeList;

/**
 * Standard Image Extractor
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class StandardImageExtractor extends ImageExtractor {
    /** @var string[] */
    private $badFileNames = [
        '\.html', '\.gif', '\.ico', 'button', 'twitter\.jpg', 'facebook\.jpg',
        'ap_buy_photo', 'digg\.jpg', 'digg\.png', 'delicious\.png',
        'facebook\.png', 'reddit\.jpg', 'doubleclick', 'diggthis',
        'diggThis', 'adserver', '\/ads\/', 'ec\.atdmt\.com', 'mediaplex\.com',
        'adsatt', 'view\.atdmt',
    ];

    /** @var string[] */
    private static $KNOWN_IMG_DOM_NAMES = [
        'yn-story-related-media',
        'cnn_strylccimg300cntr',
        'big_photo',
        'ap-smallphoto-a'
    ];

    /** @var int */
    private static $MAX_BYTES_SIZE = 15728640;

    /** @var int */
    private static $MIN_WIDTH = 50;

    /** @var Configuration */
    private $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @param Article $article
     *
     * @return Image|null
     */
    public function getBestImage(Article $article) {
        $image = $this->checkForKnownElements($article);

        if ($image) {
            return $image;
        }

        $image = $this->checkForMetaTag($article);

        if ($image) {
            return $image;
        }

        $image = $this->checkForLargeImages($article, $article->getTopNode(), 0, 0);

        if ($image) {
            return $image;
        }

        return null;
    }

    /**
     * Prefer Twitter images (as they tend to have the right size for us), then Open Graph images
     * (which seem to be smaller), and finally linked images.
     *
     * @param Article $article
     *
     * @return Image|null
     */
    private function checkForMetaTag(Article $article) {
        $image = $this->checkForTwitterTag($article);

        if ($image) {
            return $image;
        }

        $image = $this->checkForOpenGraphTag($article);

        if ($image) {
            return $image;
        }

        $image = $this->checkForLinkTag($article);

        if ($image) {
            return $image;
        }

        return null;
    }

    /**
     * although slow the best way to determine the best image is to download them and check the actual dimensions of the image when on disk
     * so we'll go through a phased approach...
     * 1. get a list of ALL images from the parent node
     * 2. filter out any bad image names that we know of (gifs, ads, etc..)
     * 3. do a head request on each file to make sure it meets our bare requirements
     * 4. any images left over let's do a full GET request, download em to disk and check their dimensions
     * 5. Score images based on different factors like height/width and possibly things like color density
     *
     * @param Article $article
     * @param DOMElement $node
     * @param int $parentDepthLevel
     * @param int $siblingDepthLevel
     *
     * @return Image|null
     */
    private function checkForLargeImages(Article $article, DOMElement $node, $parentDepthLevel, $siblingDepthLevel) {
        $goodImages = $this->getImageCandidates($article, $node);

        $scoredImages = $this->downloadImagesAndGetResults($article, $goodImages, $parentDepthLevel);

        ksort($scoredImages);

        if (!empty($scoredImages)) {
            foreach ($scoredImages as $imageScore => $scoredImage) {
                $mainImage = new Image();
                $mainImage->setImageSrc($scoredImage->getImgSrc());
                $mainImage->setImageExtractionType('bigimage');
                $mainImage->setConfidenceScore(100 / count($scoredImages));
                $mainImage->setImageScore($imageScore);
                $mainImage->setBytes($scoredImage->getBytes());
                $mainImage->setHeight($scoredImage->getHeight());
                $mainImage->setWidth($scoredImage->getWidth());

                return $mainImage;
            }
        } else {
            $depthObj = $this->getDepthLevel($node, $parentDepthLevel, $siblingDepthLevel);

            if ($depthObj) {
                return $this->checkForLargeImages($article, $depthObj->node, $depthObj->parentDepth, $depthObj->siblingDepth);
            }
        }

        return null;
    }

    /**
     * @param DOMElement $node
     * @param int $parentDepth
     * @param int $siblingDepth
     *
     * @return object|null
     */
    private function getDepthLevel(DOMElement $node, $parentDepth, $siblingDepth) {
        if (is_null($node)) {
            return null;
        }

        $MAX_PARENT_DEPTH = 2;

        if ($parentDepth > $MAX_PARENT_DEPTH) {
            return null;
        }

        $siblingNode = clone $node;

        do {
            $siblingNode = $siblingNode->previousSibling;
        } while ($siblingNode && $siblingNode->nodeType != XML_ELEMENT_NODE);

        if (is_null($siblingNode)) {
            if ($node->parentNode instanceof DOMDocument) {
                return null;
            }

            return (object)[
                'node' => $node->parentNode,
                'parentDepth' => $parentDepth + 1,
                'siblingDepth' => 0,
            ];
        }

        return (object)[
            'node' => $siblingNode,
            'parentDepth' => $parentDepth,
            'siblingDepth' => $siblingDepth + 1,
        ];
    }

    /**
     * download the images to temp disk and set their dimensions
     * <p/>
     * we're going to score the images in the order in which they appear so images higher up will have more importance,
     * we'll count the area of the 1st image as a score of 1 and then calculate how much larger or small each image after it is
     * we'll also make sure to try and weed out banner type ad blocks that have big widths and small heights or vice versa
     * so if the image is 3rd found in the dom it's sequence score would be 1 / 3 = .33 * diff in area from the first image
     *
     * @param Article $article
     * @param DOMElement[] $images
     * @param int $depthLevel
     *
     * @return LocallyStoredImage[]
     */
    private function downloadImagesAndGetResults(Article $article, $images, $depthLevel) {
        $results = [];
        $i = 1;
        $initialArea = 0;

        // Limit to the first 30 images
        $images = array_slice($images, 0, 30);

        foreach ($images as $image) {
            $locallyStoredImage = $this->getLocallyStoredImage($this->buildImagePath($article, $image->getAttribute('src')));

            if (!empty($locallyStoredImage) && $this->isWorthyImage($locallyStoredImage, $depthLevel)) {
                $sequenceScore = 1 / $i;
                $area = $locallyStoredImage->getWidth() * $locallyStoredImage->getHeight();

                if ($initialArea == 0) {
                    $initialArea = $area * 1.48;
                    $totalScore = 1;
                } else {
                    $areaDifference = $area * $initialArea;
                    $totalScore = $sequenceScore * $areaDifference;
                }

                $i++;

                $results[$totalScore] = $locallyStoredImage;
            }
        }

        return $results;
    }

    /**
     * @param LocallyStoredImage $locallyStoredImage
     * @param int $depthLevel
     *
     * @return bool
     */
    private function isWorthyImage($locallyStoredImage, $depthLevel) {
        if ($locallyStoredImage->getWidth() <= self::$MIN_WIDTH
          || $locallyStoredImage->getFileExtension() == 'NA'
          || ($depthLevel < 1 && $locallyStoredImage->getWidth() < 300) || $depthLevel >= 1
          || $this->isBannerDimensions($locallyStoredImage->getWidth(), $locallyStoredImage->getHeight())) {
            return false;
        }

        return true;
    }

    /**
     * @param Article $article
     *
     * @return Image[]
     */
    public function getAllImages(Article $article) {
        $results = [];
        $imageUrls = [];

        $images = $article->getTopNode()->filter('img');

        // Generate a complete URL for each image
        foreach ($images as $image) {
            $imageUrls[] = $this->buildImagePath($article, $image->getAttribute('src'));
        }

        $localImages = $this->getLocallyStoredImages($imageUrls);

        foreach ($localImages as $localImage) {
            $image = new Image();
            $image->setImageSrc($localImage->getImgSrc());
            $image->setBytes($localImage->getBytes());
            $image->setHeight($localImage->getHeight());
            $image->setWidth($localImage->getWidth());
            $image->setImageExtractionType('all');
            $image->setConfidenceScore(0);

            $results[] = $image;
        }

        return $results;
    }

    /**
     * returns true if we think this is kind of a bannery dimension
     * like 600 / 100 = 6 may be a fishy dimension for a good image
     *
     * @param int $width
     * @param int $height
     */
    private function isBannerDimensions($width, $height) {
        if ($width == $height) {
            return false;
        }

        if ($width > $height) {
            $diff = $width / $height;
            if ($diff > 5) {
                return true;
            }
        }

        if ($height > $width) {
            $diff = $height / $width;
            if ($diff > 5) {
                return true;
            }
        }

        return false;
    }

    /**
     * takes a list of image elements and filters out the ones with bad names
     *
     * @param DOMNodeList $images
     *
     * @return DOMElement[]
     */
    private function filterBadNames(DOMNodeList $images) {
        $goodImages = [];

        foreach ($images as $image) {
            if ($this->isOkImageFileName($image)) {
                $goodImages[] = $image;
            } else {
                $image->parentNode->removeChild($image);
            }
        }

        return $goodImages;
    }

    /**
     * will check the image src against a list of bad image files we know of like buttons, etc...
     *
     * @param DOMElement $imageNode
     *
     * @return bool
     */
    private function isOkImageFileName(DOMElement $imageNode) {
        $imgSrc = $imageNode->getAttribute('src');

        if (empty($imgSrc)) {
            return false;
        }

        $regex = '@' . implode('|', $this->badFileNames) . '@i';

        if (preg_match($regex, $imgSrc)) {
            return false;
        }

        return true;
    }

    /**
     * @param Article $article
     * @param DOMElement $node
     *
     * @return DOMElement[]
     */
    private function getImageCandidates(Article $article, DOMElement $node) {
        $images = $node->filter('img');
        $filteredImages = $this->filterBadNames($images);
        $goodImages = $this->findImagesThatPassByteSizeTest($article, $filteredImages);

        return $goodImages;
    }

    /**
     * loop through all the images and find the ones that have the best bytes to even make them a candidate
     *
     * @param Article $article
     * @param DOMElement[] $images
     *
     * @return DOMElement[]
     */
    private function findImagesThatPassByteSizeTest(Article $article, $images) {
        $i = 0; /** @todo Re-factor how the LocallyStoredImage => Image relation works ? Note: PHP 5.6.x adds a 3rd argument to array_filter() to pass the key as well as value. */

        // Limit to the first 30 images
        $images = array_slice($images, 0, 30);

        // Generate a complete URL for each image
        $imageUrls = array_map(function($image) use ($article) {
            return $this->buildImagePath($article, $image->getAttribute('src'));
        }, $images);

        $localImages = $this->getLocallyStoredImages($imageUrls, true);

        $results = array_filter($localImages, function($localImage) use($images, $i) {
            $image = $images[$i++];

            $bytes = $localImage->getBytes();

            if ($bytes < $this->config->getMinBytesForImages() && $bytes != 0 || $bytes > self::$MAX_BYTES_SIZE) {
                $image->remove();

                return false;
            }

            return true;
        });

        return $results;
    }

    /**
     * checks to see if we were able to find feature image tags on this page
     *
     * @param Article $article
     *
     * @return Image|null
     */
    private function checkForLinkTag(Article $article) {
        return $this->checkForTag($article, 'link[rel="image_src"]', 'href', 'linktag');
    }

    /**
     * checks to see if we were able to find open graph tags on this page
     *
     * @param Article $article
     *
     * @return Image|null
     */
    private function checkForOpenGraphTag(Article $article) {
        return $this->checkForTag($article, 'meta[property="og:image"],meta[name="og:image"]', 'content', 'opengraph');
    }

    /**
     * checks to see if we were able to find twitter tags on this page
     *
     * @param Article $article
     *
     * @return Image|null
     */
    private function checkForTwitterTag(Article $article) {
        return $this->checkForTag($article, 'meta[property="twitter:image"],meta[name="twitter:image"],meta[property="twitter:image:src"],meta[name="twitter:image:src"]', 'content', 'twitter');
    }

    /**
     * @param Article $article
     * @param string $selector
     * @param string $attr
     * @param string $type
     *
     * @return Image|null
     */
    private function checkForTag(Article $article, $selector, $attr, $type) {
        $meta = $article->getRawDoc()->filter($selector);

        if (!$meta->count()) {
            return null;
        }

        $node = $meta->first();

        if (!($node instanceof DOMElement)) {
            return null;
        }

        if (!$node->hasAttribute($attr)) {
            return null;
        }

        $imagePath = $this->buildImagePath($article, $node->getAttribute($attr));
        $mainImage = new Image();
        $mainImage->setImageSrc($imagePath);
        $mainImage->setImageExtractionType($type);
        $mainImage->setConfidenceScore(100);

        $locallyStoredImage = $this->getLocallyStoredImage($mainImage->getImageSrc());

        if (!empty($locallyStoredImage)) {
            $mainImage->setBytes($locallyStoredImage->getBytes());
            $mainImage->setHeight($locallyStoredImage->getHeight());
            $mainImage->setWidth($locallyStoredImage->getWidth());
        }

        return $this->ensureMinimumImageSize($mainImage);
    }

    /**
     * @param Image $mainImage
     *
     * @return Image|null
     */
    private function ensureMinimumImageSize(Image $mainImage) {
        if ($mainImage->getWidth() >= $this->config->getMinWidth()
          && $mainImage->getHeight() >= $this->config->getMinHeight()) {
            return $mainImage;
        }

        return null;
    }

    /**
     * @param string $imageSrc
     * @param bool $returnAll
     *
     * @return LocallyStoredImage|null
     */
    public function getLocallyStoredImage($imageSrc, $returnAll = false) {
        $locallyStoredImages = ImageUtils::storeImagesToLocalFile([$imageSrc], $returnAll, $this->config);

        return array_shift($locallyStoredImages);
    }

    /**
     * @param string[] $imageSrcs
     * @param bool $returnAll
     *
     * @return LocallyStoredImage[]
     */
    public function getLocallyStoredImages($imageSrcs, $returnAll = false) {
        return ImageUtils::storeImagesToLocalFile($imageSrcs, $returnAll, $this->config);
    }

    /**
     * @param Article $article
     *
     * @return string
     */
    public function getCleanDomain($article) {
        return implode('.', array_slice(explode('.', $article->getDomain()), -2, 2));
    }

    /**
     * in here we check for known image contains from sites we've checked out like yahoo, techcrunch, etc... that have
     * known  places to look for good images.
     * //todo enable this to use a series of settings files so people can define what the image ids/classes are on specific sites
     *
     * @param Article $article
     *
     * @return Image|null
     */
    public function checkForKnownElements(Article $article) {
        if (!$article->getRawDoc()) {
            return null;
        }

        $knownImgDomNames = self::$KNOWN_IMG_DOM_NAMES;

        $domain = $this->getCleanDomain($article);

        $customSiteMapping = $this->customSiteMapping();

        if (isset($customSiteMapping[$domain])) {
            foreach (explode('|', $customSiteMapping[$domain]) as $class) {
                $knownImgDomNames[] = $class;
            }
        }

        $knownImage = null;

        foreach ($knownImgDomNames as $knownName) {
            $known = $article->getRawDoc()->filter('#' . $knownName);

            if (!$known->count()) {
                $known = $article->getRawDoc()->filter('.' . $knownName);
            }

            if ($known->count()) {
                $mainImage = $known->first()->filter('img');

                if ($mainImage->count()) {
                    $knownImage = $mainImage->first();
                }
            }
        }

        if (is_null($knownImage)) {
            return null;
        }

        $knownImgSrc = $knownImage->getAttribute('src');

        $mainImage = new Image();
        $mainImage->setImageSrc($this->buildImagePath($article, $knownImgSrc));
        $mainImage->setImageExtractionType('known');
        $mainImage->setConfidenceScore(90);

        $locallyStoredImage = $this->getLocallyStoredImage($mainImage->getImageSrc());

        if (!empty($locallyStoredImage)) {
            $mainImage->setBytes($locallyStoredImage->getBytes());
            $mainImage->setHeight($locallyStoredImage->getHeight());
            $mainImage->setWidth($locallyStoredImage->getWidth());
        }

        return $this->ensureMinimumImageSize($mainImage);
    }

    /**
     * This method will take an image path and build out the absolute path to that image
     * using the initial url we crawled so we can find a link to the image if they use relative urls like ../myimage.jpg
     *
     * @param Article $article
     * @param string $imageSrc
     *
     * @return string
     */
    private function buildImagePath(Article $article, $imageSrc) {
        $articleUrlParts = parse_url($article->getFinalUrl());
        $imageUrlParts = parse_url($imageSrc);

        return http_build_url($articleUrlParts, $imageUrlParts, HTTP_URL_JOIN_PATH);
    }

    /** @var string[] */
    private static $CUSTOM_SITE_MAPPING = [];

    /**
     * @param string[]
     */
    private function customSiteMapping() {
        if (empty(self::$CUSTOM_SITE_MAPPING)) {
            $file = __DIR__ . '/../../resources/images/known-image-css.txt';

            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($file)));

            foreach ($lines as $line) {
                list($domain, $css) = explode('^', $line);

                self::$CUSTOM_SITE_MAPPING[$domain] = $css;
            }
        }

        return self::$CUSTOM_SITE_MAPPING;
    }
    
}
