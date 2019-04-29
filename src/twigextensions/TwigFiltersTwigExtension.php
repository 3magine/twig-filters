<?php
/**
 * Twig Filters plugin for Craft CMS 3.x
 *
 * Extra twig filters
 *
 * @link      https://www.3magine.com/
 * @copyright Copyright (c) 2019 Karl Schellenberg
 */

namespace threemagine\twigfilters\twigextensions;

use threemagine\twigfilters\TwigFilters;
use \Twig_SimpleFilter;

use Craft;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    Karl Schellenberg
 * @package   TwigFilters
 * @since     1.0.0
 */
class TwigFiltersTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'TwigFilters';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('firstimg', array($this, 'extractFirstImage')),
            new Twig_SimpleFilter('firstimgsrc', array($this, 'extractFirstImageSrc')),
            new Twig_SimpleFilter('excerpt', array($this, 'excerpt')),
            new Twig_SimpleFilter('hasTwoSentences', array($this, 'hasTwoSentences')),
            new Twig_SimpleFilter('firstSentence', array($this, 'firstSentence')),
            new Twig_SimpleFilter('secondSentence', array($this, 'secondSentence')),
            new Twig_SimpleFilter('wistia', array($this, 'isWistia')),
            
            new Twig_SimpleFilter('stripparagraphs', array($this, 'stripParagraphs')),
			new Twig_SimpleFilter('fiximgs', array($this, 'fixImages')),
			new Twig_SimpleFilter('is_europe', array($this, 'is_europe')),
			new Twig_SimpleFilter('cookie_consent', array($this, 'cookie_consent'))
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
    * @return array
     */
    public function getFunctions()
    {
        return [
            // new \Twig_SimpleFunction('someFunction', [$this, 'someInternalFunction']),
        ];
    }

    /**
     * Our function called via Twig; it can do anything you want
     *
     * @param null $text
     *
     * @return string
     */
    // public function someInternalFunction($text = null)
    // {
    //     $result = $text . " in the way";

    //     return $result;
    // }


    public function isWistia($html)
    {
        if(preg_match('/wistia/',$html)){

        if(preg_match('/medias/',$html)){
            $html = preg_split('/medias\//',$html);
            $html = preg_split('/\.jsonp/',$html[1]);
        }else{
            $html = preg_split('/\/iframe\//',$html);
            $html = preg_split('/\?/',$html[1]);
        }
        return $html[0];
        }
        return false;
    }
    public function extractFirstImage($html)
    {
        preg_match_all('/<img[^>]+>/i',$html, $images);
        
        if(count($images[0]))
        $html = $images[0][0];
        else
        $html = '';

        return $html;
    }
    public function extractFirstImageSrc($html)
    {
        //preg_match_all('/<img[^>]+>/i',$html, $images);
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $html, $images);
        
        if(count($images[1]))
        $src = $images[1][0];
        else
        $src = false;

        return $src;
    }
    public function excerpt($text, $length = 400, $options = array()) {
        //remove captions:
        $text = preg_replace('#(<figcaption.*?>).*?(</figcaption>)#', '$1$2', $text);
        
        $text = strip_tags($text,'<a>');

        $default = array(
            'ending' => '...', 'exact' => true, 'html' => true
        );
        $options = array_merge($default, $options);
        extract($options);

        if ($html) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                //return $text;
                return $html;
            }
            $totalLength = mb_strlen(strip_tags($ending));
            $openTags = array();
            $truncate = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                //return $text;
                return $html;
            } else {
                $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
            }
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if (isset($spacepos)) {
                if ($html) {
                    $bits = mb_substr($truncate, $spacepos);
                    preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                    if (!empty($droppedTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    }
                }
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }
        $truncate .= $ending;

        if ($html) {
            foreach ($openTags as $tag) {
                $truncate .= '</'.$tag.'>';
            }
        }

        return $truncate;
    }
    /* support for homepage split title animations */
    public function hasTwoSentences($html)
    {
        //stripparagraphs
        $html = preg_replace('/<p[^>]*?>/', '', $html);
        $html = str_replace('</p>', '</br></br>', $html);
        $html = preg_replace('/<\/br><\/br>$/','',$html);

        $html = preg_split('/\./',$html);
        
        return count($html) > 1;
    }
    public function firstSentence($html)
    {
        //stripparagraphs
        $html = preg_replace('/<p[^>]*?>/', '', $html);
        $html = str_replace('</p>', '</br></br>', $html);
        $html = preg_replace('/<\/br><\/br>$/','',$html);

        $html = preg_split('/\./',$html);

        return $html[0];
    }
    public function secondSentence($html)
    {
        //stripparagraphs
        $html = preg_replace('/<p[^>]*?>/', '', $html);
        $html = str_replace('</p>', '</br></br>', $html);
        $html = preg_replace('/<\/br><\/br>$/','',$html);

        $html = preg_split('/\./',$html);

        return $html[1];
    }
    public function stripParagraphs($html)
    {
        $html = preg_replace('/<p[^>]*?>/', '', $html);
        $html = str_replace('</p>', '</br></br>', $html);
        $html = preg_replace('/<\/br><\/br>$/','',$html);

        return $html;
    }
    public function fixImages($html)
    {
        $html = preg_replace('%(.*?)<p>\s*(<img[^<]+?)\s*(<br\s*/>)*</p>(.*)%is', '$1$2$4', $html);

        return $html;
    }
    public function is_europe($country_code)
    {
        $eu_country_codes = array(
        'AT'=>'Austria',
        'BE'=>'Belgium',
        'BG'=>'Bulgaria',
        'HR'=>'Croatia',
        'CY'=>'Cyprus',
        'CZ'=>'Czech Republic',
        'DK'=>'Denmark',
        'EE'=>'Estonia',
        'FI'=>'Finland',
        'FR'=>'France',
        'DE'=>'Germany',
        'GR'=>'Greece',
        'HU'=>'Hungary',
        'IE'=>'Ireland',
        'IT'=>'Italy',
        'LV'=>'Latvia',
        'LT'=>'Lithuania',
        'LU'=>'Luxembourg',
        'MT'=>'Malta',
        'NL'=>'Netherlands',
        'PL'=>'Poland',
        'PT'=>'Portugal',
        'RO'=>'Romania',
        'SK'=>'Slovakia',
        'SI'=>'Slovenia',
        'ES'=>'Spain',
        'SE'=>'Sweden',
        'GB'=>'United Kingdom'
        );
        return array_key_exists($country_code,$eu_country_codes);
    }
    public function cookie_consent($country_code){
        //for non Europe enable by default
        if(!$this->is_europe($country_code)) return true;

        //for europe check if user gave cookie consent:
        //cookieconsent_status = deny|dismiss. if empty or "deny", keep cookies disabled
        $consent = !isset($_COOKIE['cookieconsent_status']) ? false : $_COOKIE['cookieconsent_status'] == "dismiss";
        return $consent; 
    }
}
