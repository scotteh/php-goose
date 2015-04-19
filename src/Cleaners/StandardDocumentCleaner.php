<?php
/**
 * Standard Document Cleaner
 *
 * @package  Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Goose\Cleaners;

use Goose\Article;
use Goose\Configuration;
use Goose\Utils\Debug;

class StandardDocumentCleaner extends DocumentCleaner implements DocumentCleanerInterface {
    /** @var array Element id/class/name to be removed that start with */
    private $startsWithNodes = [
        'adspot', 'conditionalAd-', 'hidden-', 'social-', 'publication', 'share-',
        'hp-', 'ad-', 'recommended-'
    ];

    /** @var array Element id/class/name to be removed that equal */
    private $equalsNodes = [
        'side', 'links', 'inset', 'print', 'fn', 'ad',
    ];

    /** @var array Element id/class/name to be removed that end with */
    private $endsWithNodes = [
        'meta'
    ];

    /** @var array Element id/class/name to be removed that contain */
    private $searchNodes = [
        'combx', 'retweet', 'mediaarticlerelated', 'menucontainer', 'navbar',
        'storytopbar-bucket', 'utility-bar', 'inline-share-tools', 'comment', // not commented
        'PopularQuestions', 'contact', 'foot', 'footer', 'Footer', 'footnote',
        'cnn_strycaptiontxt', 'cnn_html_slideshow', 'cnn_strylftcntnt',
        'shoutbox', 'sponsor', 'tags', 'socialnetworking', 'socialNetworking', 'scroll', // not scrollable
        'cnnStryHghLght', 'cnn_stryspcvbx', 'pagetools', 'post-attributes',
        'welcome_form', 'contentTools2', 'the_answers', 'communitypromo', 'promo_holder',
        'runaroundLeft', 'subscribe', 'vcard', 'articleheadings', 'date',
        'popup', 'author-dropdown', 'tools', 'socialtools', 'byline',
        'konafilter', 'KonaFilter', 'breadcrumbs', 'wp-caption-text', 'source',
        'legende', 'ajoutVideo', 'timestamp', 'js_replies', 'creative_commons', 'topics',
        'pagination', 'mtl', 'author', 'credit', 'toc_container', 'sharedaddy', 'ad', 'po'
    ];

    /** @var array Element tagNames exempt from removal */
    private $exceptionSelectors = [
        'html', 'body',
    ];

    /**
     * Clean the contents of the supplied article document
     *
     * @param Goose\Article $article
     *
     * @return null
     */
    public function clean(Article $article) {
        $this->document($article->getDoc());

        $this->removeComments();
        $this->cleanTextTags();
        $this->removeDropCaps();
        $this->removeScriptsAndStyles();
        $this->removeUselessTags();
        $this->cleanBadTags();
        $this->removeNodesViaFilter("[%s='caption']");
        $this->removeNodesViaFilter("[%s*=' google ']");
        $this->removeNodesViaFilter("[%s*='more']:not([%s^=entry-])", 2);
        $this->removeNodesViaFilter("[%s*='facebook']:not([%s*='-facebook'])", 2);
        $this->removeNodesViaFilter("[%s*='facebook-broadcasting']");
        $this->removeNodesViaFilter("[%s*='twitter']:not([%s*='-twitter'])", 2);
        $this->cleanUpSpanTagsInParagraphs();
        $this->convertWantedTagsToParagraphs(['div', 'span', 'article']);
        //$this->convertDivsToParagraphs('div');
        //$this->convertDivsToParagraphs('span');
    }

    /**
     * Remove comments
     *
     * @return null
     */
    private function removeComments() {
        $comments = $this->document()->filterXpath('//comment()');

        foreach ($comments as $comment) {
            $comment->parentNode->removeChild($comment);
        }
    }

    /**
     * Replaces various tags with \DOMText nodes.
     *
     * @return null
     */
    private function cleanTextTags() {
        $ems = $this->document()->filter('em, strong, b, i, strike, del, ins');

        foreach ($ems as $node) {
            $images = $node->filter('img');

            if ($images->length == 0) {
                $node->parentNode->replaceChild(new \DOMText(trim($node->textContent) . ' '), $node);
            }
        }
    }

    /**
     * Takes care of the situation where you have a span tag nested in a paragraph tag
     *
     * @return null
     */
    private function cleanUpSpanTagsInParagraphs() {
        $spans = $this->document()->filter('span');

        foreach ($spans as $item) {
            if ($item->parentNode->nodeName == 'p') {
                $item->parentNode->replaceChild(new \DOMText($item->textContent), $item);
            }
        }
    }

    /**
     * Remove those css drop caps where they put the first letter in big text in the 1st paragraph
     *
     * @return null
     */
    private function removeDropCaps() {
        $items = $this->document()->filter('span[class~=dropcap], span[class~=drop_cap]');

        foreach ($items as $item) {
            $item->parentNode->replaceChild(new \DOMText($item->textContent), $item);
        }
    }

    /**
     * Remove unwanted script/style elements
     *
     * @return null
     */
    private function removeScriptsAndStyles() {
        $scripts = $this->document()->filter('script');

        foreach ($scripts as $item) {
            $item->parentNode->removeChild($item);
        }

        $styles = $this->document()->filter('style');

        foreach ($styles as $style) {
            $style->parentNode->removeChild($style);
        }
    }

    /**
     * Remove unwanted elements based on tagName
     *
     * @return null
     */
    private function removeUselessTags() {
        $tags = [
            'header', 'footer', 'input', 'form', 'button', 'aside', 'meta'
        ];

        foreach ($tags as &$tag) {
            $nodes = $this->document()->filter($tag);

            foreach ($nodes as $node) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Remove unwanted junk elements based on pre-defined CSS selectors
     *
     * @return null
     */
    private function cleanBadTags() {
        $lists = [
            "[%s^='%s']" => $this->startsWithNodes,
            "[%s*='%s']" => $this->searchNodes,
            "[%s$='%s']" => $this->endsWithNodes,
            "[%s='%s']" => $this->equalsNodes,
        ];

        $attrs = [
            'id',
            'class',
            'name',
        ];

        $exceptions = array_map(function($value){
            return ':not(' . $value . ')';
        }, $this->exceptionSelectors);

        $exceptions = implode('', $exceptions);

        foreach ($lists as $expr => $list) {
            foreach ($list as $value) {
                foreach ($attrs as $attr) {
                    $selector = sprintf($expr, $attr, $value) . $exceptions;

                    foreach ($this->document()->filter($selector) as $node) {
                        $node->parentNode->removeChild($node);
                    }
                }
            }
        }
    }

    /**
     * Remove nodes by CSS selector.
     *
     * @param string $pattern CSS selector - uses printf formatting to substitute attribute names
     * @param int $length Number of attribute name substitutions required
     *
     * @return null
     */
    private function removeNodesViaFilter($pattern, $length = 1) {
        $attrs = [
            'id',
            'class',
        ];

        foreach ($attrs as $attr) {
            $args = [
                $pattern
            ];

            for ($i = 0; $i < $length; $i++) {
                $args[] = $attr;
            }

            $selector = call_user_func_array('sprintf', $args);

            foreach ($this->document()->filter($selector) as $node) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Replace supplied element with <p> new element.
     *
     * @param Goose\DOM\DOMElement $div
     *
     * @return null
     */
    private function replaceElementsWithPara($div) {
        $el = $this->document()->createElement('p');

        foreach ($div->childNodes as $child) {
            $child = $child->cloneNode(true);
            $el->appendChild($child);
        }

        foreach ($div->attributes as $attr) {
            $el->setAttribute($attr->localName, $attr->nodeValue);
        }

        $div->parentNode->replaceChild($el, $div);
    }

    /**
     * Convert wanted elements to <p> elements.
     *
     * @param array $wantedTags tagNames of base elements to select
     *
     * @return null
     */
    private function convertWantedTagsToParagraphs($wantedTags) {
        $tags = ['a', 'blockquote', 'dl', 'div', 'img', 'ol', 'p', 'pre', 'table', 'ul'];

        $selected = $this->document()->filter(implode(', ', $wantedTags));

        foreach ($selected as $elem) {
            $items = $elem->filter(implode(', ', $tags));

            if (!$items->length) {
                $this->replaceElementsWithPara($elem);
            } else {
                $replacements = $this->getReplacementNodes($elem);

                foreach ($elem->children() as $child) {
                    $elem->removeChild($child);
                }

                foreach ($replacements as $replace) {
                    $elem->appendChild($replace);
                }
            }
        }
    }

    /**
     * @deprecated No longer used by internal code. 
     * @see self::convertWantedTagsToParagraphs()
     *
     * @param string $domType tagName of elements to select
     *
     * @return null
     *
     * @codeCoverageIgnore
     */
    private function convertDivsToParagraphs($domType) {
        $divs = $this->document()->filter($domType);

        foreach ($divs as $div) {
            if (!preg_match('@<(a|blockquote|dl|div|img|ol|p|pre|table|ul)@', $this->document()->saveXML($div))) {
                $this->replaceElementsWithPara($div);
            } else {
                $replacements = $this->getReplacementNodes($div);

                foreach ($div->children() as $child) {
                    $div->removeChild($child);
                }

                foreach ($replacements as $replace) {
                    $div->appendChild($replace);
                }
            }
        }
    }

    /**
     * Generate new <p> element with supplied content.
     *
     * @param array $replacementText Contents of element
     *
     * @return Goose\DOM\DOMElement
     */
    private function getFlushedBuffer($replacementText) {
        $fragment = $this->document()->createDocumentFragment();
        $fragment->appendXML(str_replace('&', '&amp;', implode('', $replacementText)));

        $el = $this->document()->createElement('p');
        @$el->appendChild($fragment);

        return $el;
    }

    /**
     * Generate <p> element replacements for supplied elements child nodes as required.
     *
     * @param Goose\DOM\DOMElement $div
     *
     * @return array $nodesToReturn Replacement elements
     */
    private function getReplacementNodes($div) {
        $replacementText = [];
        $nodesToReturn = [];
        $nodesToRemove = [];

        foreach ($div->childNodes as $kid) {
            if ($kid->nodeName == 'p' && count($replacementText) > 0) {
                $nodesToReturn[] = $this->getFlushedBuffer($replacementText);
                $replacementText = [];
                $nodesToReturn[] = $kid;
            } else if ($kid->nodeType == XML_TEXT_NODE) {
                $replaceText = preg_replace('@[\n\r\s\t]+@', " ", $kid->textContent);

                if (mb_strlen(trim($replaceText)) > 0) {
                    $prevSibNode = $kid->previousSibling;

                    while ($prevSibNode && $prevSibNode->nodeName == 'a' && $prevSibNode->getAttribute('grv-usedalready') != 'yes') {
                        $replacementText[] = ' ' . $this->document()->saveXML($prevSibNode) . ' ';
                        $nodesToRemove[] = $prevSibNode;
                        $prevSibNode->setAttribute('grv-usedalready', 'yes');

                        $prevSibNode = $prevSibNode->previousSibling;
                    }

                    $replacementText[] = $replaceText;

                    $nextSibNode = $kid->nextSibling;

                    while ($nextSibNode && $nextSibNode->nodeName == 'a' && $nextSibNode->getAttribute('grv-usedalready') != 'yes') {
                        $replacementText[] = ' ' . $this->document()->saveXML($nextSibNode) . ' ';
                        $nodesToRemove[] = $nextSibNode;
                        $nextSibNode->setAttribute('grv-usedalready', 'yes');

                        $nextSibNode = $nextSibNode->nextSibling;
                    }
                }

                $nodesToRemove[] = $kid;
            } else {
                if ($replacementText) {
                    $nodesToReturn[] = $this->getFlushedBuffer($replacementText);
                    $replacementText = [];
                }
                $nodesToReturn[] = $kid;
            }
        }

        if ($replacementText) {
            $nodesToReturn[] = $this->getFlushedBuffer($replacementText);
            $replacementText = [];
        }

        foreach ($nodesToRemove as $el) {
            $div->removeChild($el);
        }

        // Remove potential duplicate <a> tags.
        foreach ($nodesToRemove as $remove) {
            foreach ($nodesToReturn as $key => $return) {
                if ($remove === $return) {
                    unset($nodesToReturn[$key]);
                }
            }
        }

        return $nodesToReturn;
    }
}
