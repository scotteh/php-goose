<?php

namespace Goose\Images;

class StandardImageExtractor extends ImageExtractor {

    public function getBestImage($article) {
        $nodes = $article->getDoc()->filter('meta[property="og:image"]');

        if ($nodes->length) {
          return $nodes->item(0)->getAttribute('content');
        }

        if ($article->getTopNode()) {

          $nodes = $article->getTopNode()->filter('img[src]');

          if ($nodes->length) {
            return $nodes->item(0)->getAttribute('src');
          }
        }
    }
}
