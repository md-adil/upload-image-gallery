<?php
namespace App;

use Intervention\Image\ImageManager;

class Image
{
    protected static $manager;
    protected $image;
    public function __construct($imagePath)
    {
        if (!static::$manager) {
            static::$manager = new ImageManager([ 'driver' => 'gd' ]);
        }
        $image = static::$manager->make($imagePath);
        $image->backup();
        $this->image = $image;
    }

    public function getName()
    {
    }

    public function getExt()
    {
        $mime = explode('/', $this->image->mime());
        return $mime[1];
    }

    public function getMime()
    {
        return $this->image->mime();
    }
    
    public function getThumb()
    {
        return $this->image->reset()->resize(280, 210, function ($i) {
            $i->aspectRatio();
        })->encode();
    }

    public function getLarge()
    {
        return $this->image->reset()->resize(1280, null, function ($i) {
            $i->aspectRatio();
            $i->upsize();
        })->encode();
    }

    public function getMedium()
    {
        return $this->image->reset()->resize(800, null, function ($i) {
            $i->aspectRatio();
            $i->upsize();
        })->encode();
    }

    public function getSmall()
    {
        return $this->image->reset()->resize(472, null, function ($i) {
            $i->aspectRatio();
            $i->upsize();
        })->encode();
    }
}
