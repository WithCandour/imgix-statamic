<?php

namespace Statamic\Addons\Imgix;

use Statamic\Extend\API;

class ImgixAPI extends API
{
    /**
     * @var \Statamic\Addons\Imgix\Imgix
     */
    protected $imgix;

    protected function init() {
        $this->imgix = new Imgix;
    }

    public function buildUrl($path, $img_attributes) {
        return $this->imgix->buildUrl($path, $img_attributes);
    }

    public function buildSrcset($path, $img_attributes) {
        return $this->imgix->buildSrcset($path, $img_attributes);
    }
}
