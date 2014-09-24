<?php

namespace Goose\Utils;

class URLHelper {
    public static function getCleanedUrl($urlToCrawl) {
        $parts = (object)parse_url($urlToCrawl);
        $finalUrl = str_replace('#!', '?_escaped_fragment_=', $urlToCrawl);

        if ($parts === false) {
            throw new MalformedURLException($urlToCrawl . ' - is a malformed URL and cannot be processed');
        }

        return (object)[
            'url' => $urlToCrawl,
            'parts' => $parts,
            'linkhash' => md5($urlToCrawl),
            'finalUrl' => $finalUrl,
        ];
    }
}
