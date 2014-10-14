<?php

namespace Goose\Images;

class StandardImageExtractor extends ImageExtractor {

    private $openGraphLogos = [
        /* Websites such as "The Guardian" put their logo as a "og:image"
           therefore should be used with lower priority than the image in
           the topNode. */
        '/icons/'
    ];

    public function getBestImage($article) {
        $nodes = $article->getDoc()->filter('meta[property="og:image"]');

        $img = null;

        if ($nodes->length) {
            $img = $nodes->item(0)->getAttribute('content');
            $valid = true;

            foreach ($this->openGraphLogos as $uri) {
                if (strpos($img, $uri) !== FALSE) {
                    $valid = false;
                    break;
                }
            }

            if ($valid) return $img;
        }

        if ($article->getTopNode()) {

            $nodes = $article->getTopNode()->filter('img[src]');

            if ($nodes->length) {
                return $nodes->item(0)->getAttribute('src');
            }
        }

        return $img;
    }
}
