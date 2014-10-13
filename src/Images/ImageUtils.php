<?php

namespace Goose\Images;

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
    public static function storeImageToLocalFile($linkhash, $imageSrc, $config) {
        // @TODO: Add cache check

        $localFileName = self::handleEntity($linkhash, $imageSrc, $config);

        if ($localFileName) {
            $imageDetails = self::getImageDimensions($localFileName);

            return new LocallyStoredImage([
                'imgSrc' => $imageSrc,
                'localFileName' => $localFileName,
                'bytes' => filesize($localFileName),
                'height' => $imageDetails->height,
                'width' => $imageDetails->width,
                'fileExtension' => self::getFileExtensionName($imageDetails),
            ]);
        }

        return null;
    }

    private static function getFileExtensionName($imageDetails) {
        $extensions = [
            'image/gif' => '.gif',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
        ];

        return (
            isset($extnesions[$imageDetails->mime])
            ? $extnesions[$imageDetails->mime]
            : 'NA'
        );
    }

    private static function readExistingFileInfo($linkhash, $imageSrc, $config) {
        // TODO
    }

    private static function writeEntityContentsToDisk($entity, $linkhash, $imageSrc, $config) {
        $file = tempnam(sys_get_temp_dir(), 'goose');
    }

    private static function handleEntity($linkhash, $imageSrc, $config) {
        $file = tempnam(sys_get_temp_dir(), 'goose');

        $config = $config->getGuzzle();

        if (!is_array($config)) {
            $config = [];
        }

        $config['save_to'] = $file;

        $guzzle = new GuzzleClient();
        $response = $guzzle->get($imageSrc, $config);

        if ($response->getStatusCode() != 200) {
            return null;
        } else {
            return $file;
        }
    }
}
