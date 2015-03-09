<?php

namespace Drufony\CoreBundle\Twig;

class ImageThumbnailsExtension extends \Twig_Extension
{

    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('thumbnail', array($this, 'getThumbnail')),
        );
    }

    public function getThumbnail($originalImagePath, $style) {
        $temp = explode('/', $originalImagePath);
        $fileName = end($temp);
        $directory = implode('/', array_slice($temp, 0, count($temp)-1));

	if($style === ''){

        	return '/' . $directory .'/' . $fileName;
	}

        return '/' . $directory . '/thumbnails/' . $style . '/' . $fileName;

    }

    public function getName() {
        return 'thumbnail_extension';
    }
}
