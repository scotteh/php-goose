<?php

namespace Goose\Images;

class StandardImageExtractor extends ImageExtractor {
    private $badFileNames = [
        '\.html', '\.gif', '\.ico', 'button', 'twitter\.jpg', 'facebook\.jpg',
        'ap_buy_photo', 'digg\.jpg', 'digg\.png', 'delicious\.png',
        'facebook\.png', 'reddit\.jpg', 'doubleclick', 'diggthis',
        'diggThis', 'adserver', '\/ads\/', 'ec\.atdmt\.com', 'mediaplex\.com',
        'adsatt', 'view\.atdmt',
    ];

    private static $KNOWN_IMG_DOM_NAMES = [
        'yn-story-related-media',
        'cnn_strylccimg300cntr',
        'big_photo',
        'ap-smallphoto-a'
    ];

    private $MAX_BYTES_SIZE = 15728640;

    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function getBestImage($article) {
        $image = $this->checkForKnownElements($article);

        if ($image) {
            return $image;
        }

        $image = $this->checkForMetaTag($article);

        if ($image) {
            return $image;
        }

        $image = $this->checkForLargeImages($article, $topNode, 0, 0);

        if ($image) {
            return $image;
        }

        return null;
    }

    /**
     * Prefer Twitter images (as they tend to have the right size for us), then Open Graph images
     * (which seem to be smaller), and finally linked images.
     */
    private function checkForMetaTag($article) {
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
     * @param node
     */
    private function checkForLargeImages($article, $node, $parentDepthLevel, $siblingDepthLevel) {
        // TODO
    }

    private function getDepthLevel($node, $parentDepth, $siblingDepth) {
        // TODO
    }

    /**
     * download the images to temp disk and set their dimensions
     * <p/>
     * we're going to score the images in the order in which they appear so images higher up will have more importance,
     * we'll count the area of the 1st image as a score of 1 and then calculate how much larger or small each image after it is
     * we'll also make sure to try and weed out banner type ad blocks that have big widths and small heights or vice versa
     * so if the image is 3rd found in the dom it's sequence score would be 1 / 3 = .33 * diff in area from the first image
     *
     * @param images
     * @return
     */
    private function downloadImagesAndGetResults($images, $depthLevel) {
        // TODO
    }

    public function getAllImages($topNode, $parentDepthLevel = 0, $siblingDepthLevel = 0) {
        // TODO
    }

    /**
     * returns true if we think this is kind of a bannery dimension
     * like 600 / 100 = 6 may be a fishy dimension for a good image
     *
     * @param width
     * @param height
     */
    private function isBannerDimensions($width, $height) {
        // TODO
    }

    /**
     * takes a list of image elements and filters out the ones with bad names
     *
     * @param images
     * @return
     */
    private function filterBadNames($images) {
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
     * @return
     */
    private function isOkImageFileName($imageNode) {
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

    private function getImageCandidates($article, $node) {
        $images = $node->select('img');
        $filteredImages = $this->filterBadNames($images);
        $goodImages = $this->findImagesThatPassByteSizeTest($article, $filteredImages);

        return $goodImages;
    }

    /**
     * loop through all the images and find the ones that have the best bytez to even make them a candidate
     *
     * @param images
     * @return
     */
    private function findImagesThatPassByteSizeTest($images) {
        $cnt = 0;
        $goodImages = [];

        foreach ($images as $image) {
            if ($cnt > 30) {
                // Abort! they have over 30 images near the top node.
                return $goodImages;
            }

            $imageSrc = $image->getAttribute('src');

            $locallyStoredImage = $this->getLocallyStoredImage($article->getLinkhash(), $this->buildImagePath($article, $imageSrc));

            if ($locallyStoredImage) {
                $bytes = $locallyStoredImage->getBytes();

                if (($bytes == 0 || $bytes > $this->config->getMinBytesForImages()) && $bytes < $this->MAX_BYTES_SIZE) {
                    $goodImages[] = $image;
                }else {
                    $image->parentNode->removeChild($image);
                }
            }

            $cnt++;
        }

        return $goodImages;
    }

    /**
     * checks to see if we were able to find feature image tags on this page
     *
     * @return
     */
    private function checkForLinkTag($article) {
        return $this->checkForTag($article, 'link[rel="image_src"]', 'href', 'linktag');
    }

    /**
     * checks to see if we were able to find open graph tags on this page
     *
     * @return
     */
    private function checkForOpenGraphTag($article) {
        return $this->checkForTag($article, 'meta[property="og:image"]', 'content', 'opengraph');
    }

    /**
     * checks to see if we were able to find twitter tags on this page
     *
     * @return
     */
    private function checkForTwitterTag($article) {
        return $this->checkForTag($article, 'meta[property="twitter:image"]', 'content', 'twitter');
    }

    private function checkForTag($article, $selector, $attr, $type) {
        $meta = $article->getRawDoc()->filter($selector);

        if (!$meta->length) {
            return null;
        }

        if (!$meta->item(0)->hasAttribute($attr)) {
            return null;
        }

        $imagePath = $this->buildImagePath($article, $meta->item(0)->getAttribute($attr));
        $mainImage = new Image();
        $mainImage->setImageSrc($imagePath);
        $mainImage->setImageExtractionType($type);
        $mainImage->setConfidenceScore(100);
        $locallyStoredImage = $this->getLocallyStoredImage($article->getLinkhash(), $mainImage->getImageSrc());
        if ($locallyStoredImage) {
            $mainImage->setBytes($locallyStoredImage->getBytes());
            $mainImage->setHeight($locallyStoredImage->getHeight());
            $mainImage->setWidth($locallyStoredImage->getWidth());
        }

        return $this->ensureMinimumImageSize($mainImage);
    }

    private function ensureMinimumImageSize($mainImage) {
        if ($mainImage->getWidth() >= $this->config->getMinWidth()
          && $mainImage->getHeight() >= $this->config->getMinHeight()) {
            return $mainImage;
        }

        return false;
    }

    /**
     * returns the bytes of the image file on disk
     */
    public function getLocallyStoredImage($linkhash, $imageSrc) {
        return ImageUtils::storeImageToLocalFile($linkhash, $imageSrc, $this->config);
    }

    public function getCleanDomain($article) {
        return implode('.', array_slice(explode('.', $article->getDomain()), -2, 2));
    }

    /**
     * in here we check for known image contains from sites we've checked out like yahoo, techcrunch, etc... that have
     * known  places to look for good images.
     * //todo enable this to use a series of settings files so people can define what the image ids/classes are on specific sites
     */
    public function checkForKnownElements($article) {
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

            if (!$known->length) {
                $known = $article->getRawDoc()->filter('.' . $knownName);
            }

            if ($known->length) {
                $mainImage = $known->item(0)->filter('img');

                if ($mainImage->length) {
                    $knownImage = $mainImage->item(0);
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
        $locallyStoredImage = $this->getLocallyStoredImage($article->getLinkhash(), $mainImage->getImageSrc());
        if ($locallyStoredImage) {
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
     * @param imageSrc
     * @return
     */
    private function buildImagePath($article, $imageSrc) {
        $articleUrlParts = parse_url($article->getFinalUrl());
        $imageUrlParts = parse_url($imageSrc);

        return http_build_url($articleUrlParts, $imageUrlParts, HTTP_URL_JOIN_PATH);
    }

    private static $CUSTOM_SITE_MAPPING = array();

    private function customSiteMapping() {
        if (empty(self::$CUSTOM_SITE_MAPPING)) {
            $file = __DIR__ . '/../../resources/images/known-image-css.txt';

            $lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", file_get_contents($file)));

            foreach ($lines as $line) {
                list($domain, $css) = explode('^', $line);

                self::$CUSTOM_SITE_MAPPING[$domain] = $css;
            }
        }

        return self::$CUSTOM_SITE_MAPPING;
    }
    
}
