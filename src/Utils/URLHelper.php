<?php

namespace Goose\Utils;

use Goose\Exceptions\MalformedURLException;

/**
 * URL Helper
 *
 * @package Goose\Utils
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class URLHelper {
    /**
     * @todo Re-factor result into class
     *
     * @param string $urlToCrawl
     *
     * @return object
     */
    public static function getCleanedUrl($urlToCrawl) {
        $parts = parse_url($urlToCrawl);

        if ($parts === false) {
            throw new MalformedURLException($urlToCrawl . ' - is a malformed URL and cannot be processed');
        }

        $prefix = isset($parts['query']) && $parts['query'] ? '&' : '?';

        $finalUrl = str_replace('#!', $prefix . '_escaped_fragment_=', $urlToCrawl);

        return (object)[
            'url' => $urlToCrawl,
            'parts' => (object)$parts,
            'linkhash' => md5($urlToCrawl),
            'finalUrl' => $finalUrl,
        ];
    }
}
