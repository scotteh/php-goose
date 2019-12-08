<?php declare(strict_types=1);

namespace Goose\Modules\Extractors;

use Goose\Article;
use Goose\Traits\ArticleMutatorTrait;
use Goose\Images\{Image, ImageUtils, LocallyStoredImage};
use Goose\Modules\{AbstractModule, ModuleInterface};
use DOMWrap\{Element, NodeList};

/**
 * Image Extractor
 *
 * @package Goose\Modules\Extractors
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class ImageExtractor extends AbstractModule implements ModuleInterface {
    use ArticleMutatorTrait;

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
    private static $MAX_PARENT_DEPTH = 2;

    /** @var string[] */
    private static $CUSTOM_SITE_MAPPING = [];

    /** @inheritdoc  */
    public function run(Article $article): self {
        $this->article($article);

        if ($this->config()->get('image_fetch_best')) {
            $article->setTopImage($this->getBestImage());

            if ($this->config()->get('image_fetch_all')
              && $article->getTopNode() instanceof Element) {
                $article->setAllImages($this->getAllImages());
            }
        }

        return $this;
    }

    /**
     * @return Image|null
     */
    private function getBestImage(): ?Image {
        $image = $this->checkForKnownElements();

        if ($image) {
            return $image;
        }

        $image = $this->checkForMetaTag();

        if ($image) {
            return $image;
        }

        if ($this->article()->getTopNode() instanceof Element) {
            $image = $this->checkForLargeImages($this->article()->getTopNode(), 0, 0);

            if ($image) {
                return $image;
            }
        }

        return null;
    }

    /**
     * Prefer Twitter images (as they tend to have the right size for us), then Open Graph images
     * (which seem to be smaller), and finally linked images.
     *
     * @return Image|null
     */
    private function checkForMetaTag(): ?Image {
        $image = $this->checkForTwitterTag();

        if ($image) {
            return $image;
        }

        $image = $this->checkForOpenGraphTag();

        if ($image) {
            return $image;
        }

        $image = $this->checkForLinkTag();

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
     * @param Element $node
     * @param int $parentDepthLevel
     * @param int $siblingDepthLevel
     *
     * @return Image|null
     */
    private function checkForLargeImages(Element $node, int $parentDepthLevel, int $siblingDepthLevel): ?Image {
        $goodLocalImages = $this->getImageCandidates($node);

        $scoredLocalImages = $this->scoreLocalImages($goodLocalImages);

        ksort($scoredLocalImages);

        if (!empty($scoredLocalImages)) {
            foreach ($scoredLocalImages as $imageScore => $scoredLocalImage) {
                $mainImage = new Image();
                $mainImage->setImageSrc($scoredLocalImage->getImgSrc());
                $mainImage->setImageExtractionType('bigimage');
                $mainImage->setConfidenceScore(100 / count($scoredLocalImages));
                $mainImage->setImageScore($imageScore);
                $mainImage->setBytes($scoredLocalImage->getBytes());
                $mainImage->setHeight($scoredLocalImage->getHeight());
                $mainImage->setWidth($scoredLocalImage->getWidth());

                return $mainImage;
            }
        } else {
            $depthObj = $this->getDepthLevel($node, $parentDepthLevel, $siblingDepthLevel);

            if ($depthObj && NULL !== $depthObj->node) {
                return $this->checkForLargeImages($depthObj->node, $depthObj->parentDepth, $depthObj->siblingDepth);
            }
        }

        return null;
    }

    /**
     * @param Element $node
     * @param int $parentDepth
     * @param int $siblingDepth
     *
     * @return object|null
     */
    private function getDepthLevel(Element $node, int $parentDepth, int $siblingDepth): ?\stdClass {
        if (is_null($node) || !($node->parent() instanceof Element)) {
            return null;
        }

        if ($parentDepth > self::$MAX_PARENT_DEPTH) {
            return null;
        }

        // Find previous sibling element node
        $siblingNode = $node->preceding(function($node) {
            return $node instanceof Element;
        });

        if (is_null($siblingNode)) {
            return (object)[
                'node' => $node->parent(),
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
     * Set image score and on locally downloaded images
     *
     * we're going to score the images in the order in which they appear so images higher up will have more importance,
     * we'll count the area of the 1st image as a score of 1 and then calculate how much larger or small each image after it is
     * we'll also make sure to try and weed out banner type ad blocks that have big widths and small heights or vice versa
     * so if the image is 3rd found in the dom it's sequence score would be 1 / 3 = .33 * diff in area from the first image
     *
     * @param LocallyStoredImage[] $locallyStoredImages
     *
     * @return LocallyStoredImage[]
     */
    private function scoreLocalImages($locallyStoredImages): array {
        $results = [];
        $i = 1;
        $initialArea = 0;

        // Limit to the first 30 images
        $locallyStoredImages = array_slice($locallyStoredImages, 0, 30);

        foreach ($locallyStoredImages as $locallyStoredImage) {
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

        return $results;
    }

    /**
     * @return Image[]
     */
    private function getAllImages(): array {
        $results = [];

        $images = $this->article()->getTopNode()->find('img');

        // Generate a complete URL for each image
        $imageUrls = array_map(function($image) {
            return $this->buildImagePath($image->attr('src'));
        }, $images->toArray());

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
     * takes a list of image elements and filters out the ones with bad names
     *
     * @param \DOMWrap\NodeList $images
     *
     * @return Element[]
     */
    private function filterBadNames(NodeList $images): array {
        $goodImages = [];

        foreach ($images as $image) {
            if ($this->isOkImageFileName($image)) {
                $goodImages[] = $image;
            } else {
                $image->remove();
            }
        }

        return $goodImages;
    }

    /**
     * will check the image src against a list of bad image files we know of like buttons, etc...
     *
     * @param Element $imageNode
     *
     * @return bool
     */
    private function isOkImageFileName(Element $imageNode): bool {
        $imgSrc = $imageNode->attr('src');

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
     * @param Element $node
     *
     * @return LocallyStoredImage[]
     */
    private function getImageCandidates(Element $node): array {
        $images = $node->find('img');
        $filteredImages = $this->filterBadNames($images);
        $goodImages = $this->findImagesThatPassByteSizeTest($filteredImages);

        return $goodImages;
    }

    /**
     * loop through all the images and find the ones that have the best bytes to even make them a candidate
     *
     * @param Element[] $images
     *
     * @return LocallyStoredImage[]
     */
    private function findImagesThatPassByteSizeTest(array $images): array {
        $i = 0; /** @todo Re-factor how the LocallyStoredImage => Image relation works ? Note: PHP 5.6.x adds a 3rd argument to array_filter() to pass the key as well as value. */

        // Limit to the first 30 images
        $images = array_slice($images, 0, 30);

        // Generate a complete URL for each image
        $imageUrls = array_map(function($image) {
            return $this->buildImagePath($image->attr('src'));
        }, $images);

        $localImages = $this->getLocallyStoredImages($imageUrls, true);

        $results = array_filter($localImages, function($localImage) use($images, $i) {
            $image = $images[$i++];

            $bytes = $localImage->getBytes();

            if ($bytes < $this->config()->get('image_min_bytes') && $bytes != 0 || $bytes > $this->config()->get('image_max_bytes')) {
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
     * @return Image|null
     */
    private function checkForLinkTag(): ?Image {
        return $this->checkForTag('link[rel="image_src"]', 'href', 'linktag');
    }

    /**
     * checks to see if we were able to find open graph tags on this page
     *
     * @return Image|null
     */
    private function checkForOpenGraphTag(): ?Image {
        return $this->checkForTag('meta[property="og:image"],meta[name="og:image"]', 'content', 'opengraph');
    }

    /**
     * checks to see if we were able to find twitter tags on this page
     *
     * @return Image|null
     */
    private function checkForTwitterTag(): ?Image {
        return $this->checkForTag('meta[property="twitter:image"],meta[name="twitter:image"],meta[property="twitter:image:src"],meta[name="twitter:image:src"]', 'content', 'twitter');
    }

    /**
     * @param string $selector
     * @param string $attr
     * @param string $type
     *
     * @return Image|null
     */
    private function checkForTag(string $selector, string $attr, string $type): ?Image {
        $meta = $this->article()->getRawDoc()->find($selector);

        if (!$meta->count()) {
            return null;
        }

        $node = $meta->first();

        if (!($node instanceof Element)) {
            return null;
        }

        if (!$node->hasAttribute($attr) || !$node->attr($attr)) {
            return null;
        }

        $imagePath = $this->buildImagePath($node->attr($attr));
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
    private function ensureMinimumImageSize(Image $mainImage): ?Image {
        if ($mainImage->getWidth() >= $this->config()->get('image_min_width')
          && $mainImage->getHeight() >= $this->config()->get('image_min_height')) {
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
    private function getLocallyStoredImage(string $imageSrc, bool $returnAll = false): ?LocallyStoredImage {
        $locallyStoredImages = ImageUtils::storeImagesToLocalFile([$imageSrc], $returnAll, $this->config());

        return array_shift($locallyStoredImages);
    }

    /**
     * @param string[] $imageSrcs
     * @param bool $returnAll
     *
     * @return LocallyStoredImage[]
     */
    private function getLocallyStoredImages($imageSrcs, bool $returnAll = false): array {
        return ImageUtils::storeImagesToLocalFile($imageSrcs, $returnAll, $this->config());
    }

    /**
     * @return string
     */
    private function getCleanDomain(): string {
        return implode('.', array_slice(explode('.', $this->article()->getDomain()), -2, 2));
    }

    /**
     * In here we check for known image contains from sites we've checked out like yahoo, techcrunch, etc... that have
     * known  places to look for good images.
     *
     * @todo enable this to use a series of settings files so people can define what the image ids/classes are on specific sites
     *
     * @return Image|null
     */
    private function checkForKnownElements(): ?Image {
        if (!$this->article()->getRawDoc()) {
            return null;
        }

        $knownImgDomNames = self::$KNOWN_IMG_DOM_NAMES;

        $domain = $this->getCleanDomain();

        $customSiteMapping = $this->customSiteMapping();

        if (isset($customSiteMapping[$domain])) {
            foreach (explode('|', $customSiteMapping[$domain]) as $class) {
                $knownImgDomNames[] = $class;
            }
        }

        $knownImage = null;

        foreach ($knownImgDomNames as $knownName) {
            $known = $this->article()->getRawDoc()->find('#' . $knownName);

            if (!$known->count()) {
                $known = $this->article()->getRawDoc()->find('.' . $knownName);
            }

            if ($known->count()) {
                $mainImage = $known->first()->find('img');

                if ($mainImage->count()) {
                    $knownImage = $mainImage->first();
                }
            }
        }

        if (is_null($knownImage)) {
            return null;
        }

        $knownImgSrc = $knownImage->attr('src');

        $mainImage = new Image();
        $mainImage->setImageSrc($this->buildImagePath($knownImgSrc));
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
     * @param string $imageSrc
     *
     * @return string
     */
    private function buildImagePath(string $imageSrc): string {
        $parts = array(
            'scheme',
            'host',
            'port',
            'path',
            'query',
        );

        $imageUrlParts = parse_url($imageSrc);
        $articleUrlParts = parse_url($this->article()->getFinalUrl());
        if (isset($imageUrlParts['path'], $articleUrlParts['path']) && $imageUrlParts['path'] && $imageUrlParts['path'][0] !== '/') {
            $articleUrlDir = dirname($articleUrlParts['path']);
            $imageUrlParts['path'] = $articleUrlDir . '/' . $imageUrlParts['path'];
        }

        foreach ($parts as $part) {
            if (!isset($imageUrlParts[$part]) && isset($articleUrlParts[$part])) {
                $imageUrlParts[$part] = $articleUrlParts[$part];

            } else if (isset($imageUrlParts[$part]) && !isset($articleUrlParts[$part])) {
                break;
            }
        }

        return http_build_url($imageUrlParts, array());
    }

    /**
     * @param string[]
     *
     * @return array
     */
    private function customSiteMapping(): array {
        if (empty(self::$CUSTOM_SITE_MAPPING)) {
            $file = __DIR__ . '/../../../resources/images/known-image-css.txt';

            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($file)));

            foreach ($lines as $line) {
                list($domain, $css) = explode('^', $line);

                self::$CUSTOM_SITE_MAPPING[$domain] = $css;
            }
        }

        return self::$CUSTOM_SITE_MAPPING;
    }

}
