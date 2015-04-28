<?php

namespace Goose\Cleaners;

use Goose\Article;
use Goose\DOM\DOMText;
use Goose\DOM\DOMNodeList;
use Goose\Traits\DocumentMutatorTrait;

/**
 * Document Cleaner
 *
 * @package Goose\Cleaners
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class DocumentCleaner extends AbstractCleaner implements CleanerInterface {
    use DocumentMutatorTrait;

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
     * @param Article $article
     *
     * @return null
     */
    public function clean(Article $article) {
        $this->document($article->getDoc());

        $this->removeXPath('//comment()');
        $this->replace('em, strong, b, i, strike, del, ins', function($node) {
            return !$node->filter('img')->count();
        });
        $this->replace('span[class~=dropcap], span[class~=drop_cap]');
        $this->remove('script, style');
        $this->remove('header, footer, input, form, button, aside, meta');
        $this->removeBadTags();
        $this->remove("[id='caption'],[class='caption']");
        $this->remove("[id*=' google '],[class*=' google ']");
        $this->remove("[id*='more']:not([id^=entry-]),[class*='more']:not([class^=entry-])");
        $this->remove("[id*='facebook']:not([id*='-facebook']),[class*='facebook']:not([class*='-facebook'])");
        $this->remove("[id*='facebook-broadcasting'],[class*='facebook-broadcasting']");
        $this->remove("[id*='twitter']:not([id*='-twitter']),[class*='twitter']:not([class*='-twitter'])");
        $this->replace('span', function($node) {
            if (is_null($node->parent())) {
                return false;
            }

            return $node->parent()->is('p');
        });
        $this->convertToParagraph('div, span, article');
    }

    /**
     * Remove via CSS selectors
     *
     * @param string $selector
     * @param \Closure $callback
     *
     * @return null
     */
    private function remove($selector, \Closure $callback = null) {
        $nodes = $this->document()->filter($selector);

        foreach ($nodes as $node) {
            if (is_null($callback) || $callback($node)) {
                $node->remove();
            }
        }
    }

    /**
     * Remove using via XPath expressions
     *
     * @param string $expression
     * @param \Closure $callback
     *
     * @return null
     */
    private function removeXPath($expression, \Closure $callback = null) {
        $nodes = $this->document()->filterXPath($expression);

        foreach ($nodes as $node) {
            if (is_null($callback) || $callback($node)) {
                $node->remove();
            }
        }
    }

    /**
     * Replace node with its textual contents via CSS selectors
     *
     * @param string $selector
     * @param \Closure $callback
     *
     * @return null
     */
    private function replace($selector, \Closure $callback = null) {
        $nodes = $this->document()->filter($selector);

        foreach ($nodes as $node) {
            if (is_null($callback) || $callback($node)) {
                $node->replace(new DOMText((string)$node->text()));
            }
        }
    }

    /**
     * Remove unwanted junk elements based on pre-defined CSS selectors
     *
     * @return null
     */
    private function removeBadTags() {
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

        $exceptions = array_map(function($value) {
            return ':not(' . $value . ')';
        }, $this->exceptionSelectors);

        $exceptions = implode('', $exceptions);

        foreach ($lists as $expr => $list) {
            foreach ($list as $value) {
                foreach ($attrs as $attr) {
                    $selector = sprintf($expr, $attr, $value) . $exceptions;

                    foreach ($this->document()->filter($selector) as $node) {
                        $node->remove();
                    }
                }
            }
        }
    }

    /**
     * Replace supplied element with <p> new element.
     *
     * @param Goose\DOM\DOMElement $node
     *
     * @return null
     */
    private function replaceElementsWithPara($node) {
        $el = $this->document()->createElement('p');

        foreach ($node->children() as $child) {
            $child = $child->cloneNode(true);
            $el->appendChild($child);
        }

        foreach ($node->attributes as $attr) {
            $el->setAttribute($attr->localName, $attr->nodeValue);
        }

        $node->replace($el);
    }

    /**
     * Convert wanted elements to <p> elements.
     *
     * @param string $selector
     *
     * @return null
     */
    private function convertToParagraph($selector) {
        $nodes = $this->document()->filter($selector);

        foreach ($nodes as $node) {
            $tagNodes = $node->filter('a, blockquote, dl, div, img, ol, p, pre, table, ul');

            if (!$tagNodes->count()) {
                $this->replaceElementsWithPara($node);
            } else {
                $replacements = $this->getReplacementNodes($node);

                $node->children()->remove();
                $node->append($replacements);
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
        $fragment->appendXML(str_replace('&', '&amp;', implode(' ', $replacementText)));

        $el = $this->document()->createElement('p');
        $el->appendChild($fragment);

        return $el;
    }

    /**
     * Generate <p> element replacements for supplied elements child nodes as required.
     *
     * @param Goose\DOM\DOMElement $node
     *
     * @return DOMNodeList $nodesToReturn Replacement elements
     */
    private function getReplacementNodes($node) {
        $replacementText = [];
        $nodesToReturn = new DOMNodeList;
        $nodesToRemove = new DOMNodeList;

        foreach ($node->children() as $child) {
            if ($child->is('p') && !empty($replacementText)) {
                $nodesToReturn[] = $this->getFlushedBuffer($replacementText);
                $replacementText = [];
                $nodesToReturn[] = $child;
            } else if ($child->nodeType == XML_TEXT_NODE) {
                $replaceText = $child->text(DOM_NODE_TEXT_NORMALISED);

                if (!empty($replaceText)) {
                    // Get all previous sibling <a> nodes, the current text node, and all next sibling <a> nodes.
                    $siblings = $child->previousAll('a')->merge([$child])->merge($child->nextAll('a'));

                    foreach ($siblings as $sibling) {
                        // Place current nodes textual contents in-between previous and next nodes.
                        if ($sibling->isSameNode($child)) {
                            $replacementText[] = $replaceText;

                        // Grab the contents of any unprocessed <a> siblings and flag them for removal.
                        } else if ($sibling->getAttribute('grv-usedalready') != 'yes') {
                            $sibling->setAttribute('grv-usedalready', 'yes');

                            $replacementText[] = $this->document()->saveXML($sibling);
                            $nodesToRemove[] = $sibling;
                        }

                    }
                }

                $nodesToRemove[] = $child;
            } else {
                if (!empty($replacementText)) {
                    $nodesToReturn[] = $this->getFlushedBuffer($replacementText);
                }

                $nodesToReturn[] = $child;
            }
        }

        if (!empty($replacementText)) {
            $nodesToReturn[] = $this->getFlushedBuffer($replacementText);
        }

        $nodesToRemove->remove();

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
