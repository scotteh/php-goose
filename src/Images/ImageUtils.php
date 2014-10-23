<?php

namespace Goose\Images;

use GuzzleHttp\Pool as GuzzlePool;
use GuzzleHttp\Client as GuzzleClient;

class ImageUtils {
    public static function getImageDimensions($filePath) {
        list($width, $height, $type, $attr) = getimagesize($filePath);

        return (object)[
            'width' => $width,
            'height' => $height,
            'mime' => image_type_to_mime_type($type),
        ];
    }

    /**
     * Writes an image src http string to disk as a temporary file and returns the LocallyStoredImage object that has the info you should need
     * on the image
     */
    public static function storeImageToLocalFile($imageSrcs, $returnAll, $config) {
        $asArray = is_array($imageSrcs);

        if (!$asArray) {
            $imageSrcs = [$imageSrcs];
        }

        $localImages = self::handleEntity($imageSrcs, $returnAll, $config);

        if (empty($localImages)) {
            return null;
        }

        $locallyStoredImages = [];
        foreach ($localImages as $localImage) {
            $imageDetails = self::getImageDimensions($localImage->file);

            $locallyStoredImages[] = new LocallyStoredImage([
                'imgSrc' => $localImage->url,
                'localFileName' => $localImage->file,
                'bytes' => filesize($localImage->file),
                'height' => $imageDetails->height,
                'width' => $imageDetails->width,
                'fileExtension' => self::getFileExtensionName($imageDetails),
            ]);
        }

        return (
            $asArray
            ? $locallyStoredImages
            : $locallyStoredImages[0]
        );
    }

    private static function getFileExtensionName($imageDetails) {
        $extensions = [
            'image/gif' => '.gif',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
        ];

        return (
            isset($extensions[$imageDetails->mime])
            ? $extensions[$imageDetails->mime]
            : 'NA'
        );
    }

    private static function readExistingFileInfo($imageSrc, $config) {
        // TODO
    }

    private static function writeEntityContentsToDisk($entity, $imageSrc, $config) {
        $file = tempnam(sys_get_temp_dir(), 'goose');
    }

    private static function handleEntity($imageSrcs, $returnAll, $config) {
        $guzzle = new GuzzleClient();

        $requests = [];

        foreach ($imageSrcs as $imageSrc) {
            $file = tempnam(sys_get_temp_dir(), 'goose');

            $options = $config->getGuzzle();

            if (!is_array($options)) {
                $options = [];
            }

            $options['save_to'] = $file;

            $requests[] = $guzzle->createRequest('GET', $imageSrc, $options);
        }

        $responses = GuzzlePool::batch($guzzle, $requests);

        $results = [];
        foreach ($responses as $response) {
            if ($returnAll || $response->getStatusCode() == 200) {
                $results[] = (object)[
                    'url' => $response->getEffectiveUrl(),
                    'file' => $response->getBody()->getContents(),
                ];
            }
        }

        if (empty($results)) {
            return null;
        }

        return $results;
    }
}
