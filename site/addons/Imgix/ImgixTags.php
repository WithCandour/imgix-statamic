<?php

namespace Statamic\Addons\Imgix;

use Statamic\Extend\Tags;

class ImgixTags extends Tags
{
    private static $html_attributes = array('accesskey', 'align', 'alt', 'border', 'class', 'contenteditable', 'contextmenu', 'dir', 'height', 'hidden', 'id', 'lang', 'longdesc', 'sizes', 'style', 'tabindex', 'title', 'usemap', 'width');

    /**
     * @var \Statamic\Addons\Imgix\Imgix
     */
    protected $imgix;

    protected function init()
    {
        $this->imgix = new Imgix;
    }

    protected function categorizedAttributes()
    {
        $attrs = $this->parameters;

        if (!isset($attrs['path'])) {
            return null;
        }

        $categorized_attrs = array(
            'path' => $attrs['path'],
            'img_attributes' => array(),
            'imgix_attributes' => array()
        );

        unset($attrs['path']);

        foreach ($attrs as $key => $val) {
            $is_html_attr = in_array($key, self::$html_attributes);
            $is_data_attr = strpos($key, 'data-') === 0;
            $is_aria_attr = strpos($key, 'aria-') === 0;

            if ($is_html_attr || $is_data_attr || $is_aria_attr) {
                $categorized_attrs['img_attributes'][$key] = $val;
            } else {
                $categorized_attrs['imgix_attributes'][$key] = $val;
            }
        }

        return $categorized_attrs;
    }

    protected function buildHtmlAttributes($categorized_attrs)
    {
        $img_attributes = $categorized_attrs['img_attributes'];

        $html = '';

        foreach ($img_attributes as $key => $val) {
            $html .= " $key=\"$val\"";
        }

        return $html;
    }

    public function index()
    {
        return $this->imageUrl();
    }

    public function imageUrl()
    {
        $categorized_attrs = $this->categorizedAttributes();

        if (empty($categorized_attrs)) {
            return null;
        }

        return $this->imgix->buildUrl($categorized_attrs['path'], $categorized_attrs['imgix_attributes']);
    }

    public function imageTag()
    {
        $categorized_attrs = $this->categorizedAttributes();

        if (empty($categorized_attrs)) {
            return null;
        }

        return join('', array(
            '<img src="',
                $this->imgix->buildUrl($categorized_attrs['path'], $categorized_attrs['imgix_attributes']),
            '" ',
                $this->buildHtmlAttributes($categorized_attrs),
            '>'
        ));
    }

    public function responsiveImageTag()
    {
        $categorized_attrs = $this->categorizedAttributes();

        if (empty($categorized_attrs)) {
            return null;
        }

        return join('', array(
            '<img srcset="',
                $this->imgix->buildSrcset($categorized_attrs['path'], $categorized_attrs['imgix_attributes']),
            '" src="',
                $this->imgix->buildUrl($categorized_attrs['path'], $categorized_attrs['imgix_attributes']),
            '" ',
                $this->buildHtmlAttributes($categorized_attrs),
            '>'
        ));
    }

    public function pictureTag()
    {
        $categorized_attrs = $this->categorizedAttributes();

        if (empty($categorized_attrs)) {
            return null;
        }

        return join('', array(
            '<picture>',
                '<source srcset="',
                    $this->imgix->buildSrcset($categorized_attrs['path'], $categorized_attrs['imgix_attributes']),
                '">',
                '<img src="',
                    $this->imgix->buildUrl($categorized_attrs['path'], $categorized_attrs['imgix_attributes']),
                '" ',
                    $this->buildHtmlAttributes($categorized_attrs),
                '>',
            '</picture>'
        ));
    }

    protected function generate_source($min_width, $w, $h)
    {
        $path = $this->getParam('path');
        $standard_image = $this->imgix->buildUrl($path, ['w' => $w, 'h' => $h, 'fit' => 'crop']);
        $large_image = $this->imgix->buildUrl($path, ['w' => $w, 'h' => $h, 'fit' => 'crop', 'dpr' => '2']);
        $source = "<source media='(min-width: {$min_width}px)' srcset='{$standard_image} 1x, {$large_image} 2x'></source>";
        return $source;
    }

    protected function generate_sources($string)
    {

        $sizes = explode(',', $string);
        $sources = [];
        foreach($sizes as $size) {
            $query = explode(': ', $size);
            $min_width = $query[0];
            preg_match('/\[(.*)\]/', $query[1], $dimensions);
            $dimension_values = explode('x', $dimensions[1]);
            $sources[] = $this->generate_source($min_width, $dimension_values[0], $dimension_values[1]);
        }
        return implode('', $sources);
    }

    /**
     * Custom responsive picture tag
     * Takes a sizes attribute that will contain data about the viewport
     * queries that we should produce `<source>` tags for
     * @return string HTML picture with all the sources
     */
    public function responsivePictureTag()
    {

        $categorized_attrs = $this->categorizedAttributes();

        if (empty($categorized_attrs)) {
            return null;
        }

        return join('', array(
            "<picture>",
            $this->generate_sources($this->parameters['sizes']),
            $this->imageTag(),
            "</picture>"
        ));
    }
}
