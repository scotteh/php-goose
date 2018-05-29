<?php declare(strict_types=1);

namespace Goose\Images;

use Goose\Configuration;
use GuzzleHttp\{Client, Pool};
use GuzzleHttp\Psr7\Request;

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
    public static function getImageDimensions(string $filePath): \stdClass {
        list($width, $height, $type) = getimagesize($filePath);

        return (object)[
            'width' => (int)$width,
            'height' => (int)$height,
            'mime' => is_null( $type ) ? null : image_type_to_mime_type($type),
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
    public static function storeImagesToLocalFile($imageSrcs, bool $returnAll, Configuration $config): array {
        $localImages = self::handleEntity($imageSrcs, $returnAll, $config);

        if (empty($localImages)) {
            return [];
        }

        $locallyStoredImages = [];

        foreach ($localImages as $localImage) {
            if (empty($localImage->file)) {
                continue;
            }
            
            $imageDetails = self::getImageDimensions($localImage->file);
            if ( !$imageDetails->mime )
            	continue;

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
    private static function getFileExtensionName(\stdClass $imageDetails): string {
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
     * @return array|null
     */
    private static function handleEntity($imageSrcs, bool $returnAll, Configuration $config): ?array {
        $guzzle = new Client();

        $results = [];

        $requests = function($urls) use ($guzzle, &$results) {
            foreach ($urls as $key => $url) {
                $file = tempnam(sys_get_temp_dir(), 'goose');

                $results[] = (object)[
                    'url' => $url,
                    'file' => $file,
                ];

                yield $key => function($options) use ($guzzle, $url, $file) {
                    $options['sink'] = $file;

                    return $guzzle->sendAsync(new Request('GET', $url), $options);
                };
            }
        };

        $pool = new Pool($guzzle, $requests($imageSrcs), [
            'concurrency' => 25,
            'fulfilled' => function($response, $index) use (&$results, $returnAll) {
                if (!$returnAll && $response->getStatusCode() != 200) {
                    unset($results[$index]);
                }
            },
            'rejected' => function($reason, $index) use (&$results, $returnAll) {
                if ($returnAll) {
                    $results[$index]->file = null;
                } else {
                    unset($results[$index]);
                }
            },
            'options' => $config->get('browser'),
        ]);

        $pool->promise()->wait();

        if (empty($results)) {
            return null;
        }

        return $results;
    }
}
