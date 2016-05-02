<?php

namespace Goose\Images;

use Goose\Configuration;
use GuzzleHttp\Pool as GuzzlePool;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Image Utils
 *
 * @package Goose\Images
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class ImageUtils {
    /**
     * @param string $filePath
     *
     * @return object
     */
    public static function getImageDimensions($filePath) {
        list($width, $height, $type) = getimagesize($filePath);

        return (object)[
            'width' => $width,
            'height' => $height,
            'mime' => image_type_to_mime_type($type),
        ];
    }

    /**
     * Writes an image src http string to disk as a temporary file and returns the LocallyStoredImage object that has the info you should need
     * on the image
     *
     * @param string[] $imageSrcs
     * @param bool $returnAll
     * @param Configuration $config
     *
     * @return LocallyStoredImage[]
     */
    public static function storeImagesToLocalFile($imageSrcs, $returnAll, Configuration $config) {
        $localImages = self::handleEntity($imageSrcs, $returnAll, $config);

        if (empty($localImages)) {
            return [];
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

        return $locallyStoredImages;
    }

    /**
     * @param object $imageDetails
     *
     * @return string
     */
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

    /**
     * @param string[] $imageSrcs
     * @param bool $returnAll
     * @param Configuration $config
     *
     * @return object[]|null
     */
    private static function handleEntity($imageSrcs, $returnAll, $config) {
        $guzzle = new GuzzleClient();

        $requests = [];

        foreach ($imageSrcs as $imageSrc) {
            $file = tempnam(sys_get_temp_dir(), 'goose');

            $options = $config->get('browser');

            $options['save_to'] = $file;

            $requests[] = $guzzle->createRequest('GET', $imageSrc, $options);
        }

        $batchResults = GuzzlePool::batch($guzzle, $requests);

        $results = [];

        foreach ($batchResults as $batchResult) {
            /** @todo Handle failures gracefully */
            if ($batchResult instanceof \GuzzleHttp\Exception\ClientException) {
                if ($returnAll) {
                    $results[] = (object)[
                        'url' => $batchResult->getResponse()->getEffectiveUrl(),
                        'file' => null,
                    ];
                }
            } elseif ($batchResult instanceof \GuzzleHttp\Exception\RequestException) {
                if ($returnAll) {
                    $results[] = (object)[
                        'url' => $batchResult->getResponse()->getEffectiveUrl(),
                        'file' => null,
                    ];
                }
            } else {
                if ($returnAll || $batchResult->getStatusCode() == 200) {
                    $results[] = (object)[
                        'url' => $batchResult->getEffectiveUrl(),
                        'file' => $batchResult->getBody()->getContents(),
                    ];
                }
            }
        }

        if (empty($results)) {
            return null;
        }

        return $results;
    }
}
