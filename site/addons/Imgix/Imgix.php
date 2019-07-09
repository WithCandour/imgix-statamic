<?php

namespace Statamic\Addons\Imgix;

use Imgix\UrlBuilder;
use Statamic\Extend\Extensible;

class Imgix
{
    use Extensible;

    /**
     * @var \Imgix\UrlBuilder
     */
    protected $builder;

    public function __construct()
    {
        $builder = new UrlBuilder($this->getConfig('source'));
        $builder->setUseHttps($this->getConfig('use_https', true));

        if ($secureURLToken = $this->getConfig('secure_url_token')) {
            $builder->setSignKey($secureURLToken);
        }

        $this->builder = $builder;
    }

    public function buildUrl($path, $imgix_attributes)
    {
        $pathInfo = parse_url($path);

        if (isset($pathInfo['path'])) {
            $path = $pathInfo['path'];
        }

        return $this->builder->createURL($path, $imgix_attributes);
    }

    public function buildSrcset($path, $imgix_attributes)
    {
        $srcset_values = array();
        $resolutions = $this->getConfig('responsive_resolutions', array(1, 2));

        foreach ($resolutions as $resolution) {
            if ($resolution != 1) {
                $imgix_attributes['dpr'] = $resolution;
            }

            $srcset_value = $this->buildUrl($path, $imgix_attributes) . ' ' . $resolution . 'x';

            array_push($srcset_values, $srcset_value);
        }

        return join(',', $srcset_values);
    }
}
