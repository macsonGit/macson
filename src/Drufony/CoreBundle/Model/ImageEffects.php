<?php
/**
 * Implementation of an static class for image handling. It provides
 * all the helpers you need to manage images, apply effects, resizes,
 * crops, thumbnails, etc.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpFoundation\File\File;

/**
 * Handles effects to images. It provides functionalities for handling, saving and
 * applying effects.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class ImageEffects
{
    /**
     * Generates the derivatives for a single image
     * @image File with original image uploaded
     * @styles array with styles, if empty create all styles defined
     */
    static public function generateImageEffects($image, $styles = array())
    {
        if(empty($styles)) {
            $styles = unserialize(IMAGE_EFFECTS);
        }
        foreach($styles as $style_name => $style) {
            $source = $image->getPathname();
            $destination = self::imageThumbnailDestination($image, $style_name);
            self::imageStyleApply($style, $source, $destination);
        }
    }

    /**
     * Regenerate all image thumbnails
     * @param style array with styles to regenerate, if empty it regerates
     * all styles
     */
    static public function regenerateAllImages($style = array())
    {
        $sql = "SELECT uri FROM image i INNER JOIN file_managed fm ON fm.fid = i.fid";
        $files = db_fetchAllColumn($sql);
        foreach ($files as $fileUri) {
            if (file_exists($fileUri)) {
                self::generateImageEffects(new File($fileUri), $style);
            }
        }
    }

    /**
     * Calculates the destination path where a thumbnail image for a given node and style will be stored
     * @return the destination path
     */
    static private function imageThumbnailDestination($image, $style)
    {
        $destination = $image->getPath() . '/thumbnails/' . $style . '/' . $image->getFilename();
        return $destination;
    }

    static private function imageLoad($image)
    {
        $extension = str_replace('jpg', 'jpeg', $image['info']['extension']);
        $function = 'imagecreatefrom' . $extension;
        if (function_exists($function)) {
            $image['resource'] = $function($image['source']);
        }
        return $image;
    }

    static private function imageStyleApply($style, $origin, $destination)
    {
        $image['toolkit'] = 'gd';
        $image = self::imageGetInfo($origin);
        if(!$image['info']) {
            return FALSE;
        }

        $image = self::imageLoad($image);

        $temp = explode('/', $destination);
        $directory = implode('/', array_slice($temp, 0, count($temp)-1));
        @mkdir($directory, 0775, true);

        foreach($style as $effect) {
            if(!$newimage = self::imageEffectApply($image, $effect)) {
                return false;
            }
            $newimage['source'] = $destination;
            $image = $newimage;
            unset($newimage['file_size']);
        }

        if(file_exists($destination)) {
            @unlink($destination);
        }


        if(!self::imageSave($newimage, $destination)) {
            return FALSE;
        }
        return TRUE;
    }

    static private function imageSave($image, $destination)
    {
        $permanent_destination = $destination;
        $extension = str_replace('jpg', 'jpeg', $image['info']['extension']);
        $function = 'image' . $extension;
        if (!function_exists($function)) {
            return FALSE;
        }
        if ($extension == 'jpeg') {
            $success = $function($image['resource'], $destination, 100);
        }
        else {
            // Always save PNG images with full transparency.
            if ($extension == 'png') {
                imagealphablending($image['resource'], FALSE);
                imagesavealpha($image['resource'], TRUE);
            }
            touch($destination);
            $success = $function($image['resource'], $destination);
        }

        return $success;
    }


    /**
     * Gets the image info
     */
    static private function imageGetInfo($imagepath)
    {
        $details = FALSE;
        $data = @getimagesize($imagepath);

        if (isset($data) && is_array($data)) {
            $extensions = array(
                '1' => 'gif',
                '2' => 'jpg',
                '3' => 'png',
            );
            $extension = isset($extensions[$data[2]]) ? $extensions[$data[2]] : '';
            $details['info'] = array(
                'width' => $data[0],
                'height' => $data[1],
                'extension' => $extension,
                'mime_type' => $data['mime'],
            );
            $details['info']['file_size'] = filesize($imagepath);
            $details['source'] = $imagepath;
        }else{
        }
        return $details;
    }

    static private function imageCreateTmp($image, $width, $height)
    {
        $res = imagecreatetruecolor($width, $height);

        if ($image['info']['extension'] == 'gif') {
            // Grab transparent color index from image resource.
            $transparent = imagecolortransparent($image['resource']);

            if ($transparent >= 0) {
                // The original must have a transparent color, allocate to the new image.
                $transparent_color = imagecolorsforindex($image['resource'], $transparent);
                $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

                // Flood with our new transparent color.
                imagefill($res, 0, 0, $transparent);
                imagecolortransparent($res, $transparent);
            }
        }
        elseif ($image['info']['extension'] == 'png') {
            imagealphablending($res, FALSE);
            $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
            imagefill($res, 0, 0, $transparency);
            imagealphablending($res, TRUE);
            imagesavealpha($res, TRUE);
        }
        else {
            imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
        }

        return $res;
    }

    static private function imageResize($image, $width, $height)
    {
        $res = self::imageCreateTmp($image, $width, $height);

        if (!imagecopyresampled($res, $image['resource'], 0, 0, 0, 0, $width, $height, $image['info']['width'], $image['info']['height'])) {
            return FALSE;
        }

        imagedestroy($image['resource']);
        // Update image object.
        $image['resource'] = $res;
        $image['info']['width'] = $width;
        $image['info']['height'] = $height;
        return $image;
    }


    static private function imageResizeEffect(&$image, $data)
    {
        if (!$newimage = self::imageResize($image,(int) round($data['width']), (int) round($data['height']))) {
            return FALSE;
        }
        return $newimage;
    }


    /** Crops an image.
     *
     * @return the modified image or false.
     */
    static private function imageCrop($image, $x, $y, $width, $height)
    {
        $res = self::imageCreateTmp($image, $width, $height);

        if (!imagecopyresampled($res, $image['resource'], 0, 0, $x, $y, $width, $height, $width, $height)) {
            return FALSE;
        }

        // Destroy the original image and return the modified image.
        imagedestroy($image['resource']);
        $image['resource'] = $res;
        $image['info']['width'] = $width;
        $image['info']['height'] = $height;
        return $image;
    }


    /**
     * Scales and crops an image
     * @return the modified image or false.
     */
    static private function imageScaleAndCrop($image, $width, $height)
    {
        $scale = max($width / $image['info']['width'], $height / $image['info']['height']);
        $x = ($image['info']['width'] * $scale - $width) / 2;
        $y = ($image['info']['height'] * $scale - $height) / 2;

        if(!$newimage = self::imageResize($image, (int) round($image['info']['width'] * $scale), (int) round($image['info']['height'] * $scale))) {
            return false;
        }

        return self::imageCrop($newimage, $x, $y, (int) round($width), (int) round($height));
    }

    /** Scales and crops an image
     *
     * @return the modified image or false.
     */
    static private function imageScaleAndCropEffect(&$image, $data)
    {
        if(!$newimage = self::imageScaleAndCrop($image, (int) round($data['width']), (int) round($data['height']))) {
            return false;
        }
        return $newimage;
    }

    /**
     * Calculates the new dimensions when scaling an image
     *  @returns true or false, on success
     */
    static private function imageDimensionsScale(array &$dimensions, $width = NULL, $height = NULL, $upscale = FALSE) {
        $dimensions['height'] = '637';
        $dimensions['width'] = '1024';
        $width = 660;
        $heigth = 380;
        $aspect = $dimensions['height'] / $dimensions['width'];

        // Calculate one of the dimensions from the other target dimension,
        // ensuring the same aspect ratio as the source dimensions. If one of the
        // target dimensions is missing, that is the one that is calculated. If both
        // are specified then the dimension calculated is the one that would not be
        // calculated to be bigger than its target.
        if (($width && !$height) || ($width && $height && $aspect < $height / $width)) {
            $height = (int) round($width * $aspect);
        }
        else {
            $width = (int) round($height / $aspect);
        }

        // Don't upscale if the option isn't enabled.
        if (!$upscale && ($width >= $dimensions['width'] || $height >= $dimensions['height'])) {
            return FALSE;
        }

        $dimensions['width'] = $width;
        $dimensions['height'] = $height;

        return TRUE;
    }

    /**
     * Scales an image
     * @return the scaled image
     */
    static private function imageScale($image, $width, $height, $upscale = FALSE)
    {
        $dimensions = $image['info'];
        if(!self::imageDimensionsScale($dimensions, $width, $height, $upscale)) {
            return true;
        }
        return self::imageResize($image, $dimensions['width'], $dimensions['height']);
    }

    static private function imageScaleEffect(&$image, $data)
    {
        if(!$newimage = self::imageScale($image, $data['width'], $data['height'], $data['upscale'])) {
            return false;
        }
        return $newimage;
    }

    /**
     * Accepts a keyword (center, top, left, etc) and returns it as a pixel offset.
     * @return the resulting pixel offset
     */
    static private function imageFilterKeyword($value, $current_pixels, $new_pixels)
    {
        switch ($value) {
            case 'top':
            case 'left':
                return 0;

            case 'bottom':
            case 'right':
                return $current_pixels - $new_pixels;

            case 'center':
                return $current_pixels / 2 - $new_pixels / 2;
        }
        return $value;
    }

    /**
     * Crops an image.
     * @return the cropped image or false on failure
     */
    static private function imageCropEffect(&$image, $data)
    {
        list($x, $y) = explode('-', $data['anchor']);
        $x = self::imageFilterKeyword($x, $image['info']['width'], $data['width']);
        $y = self::imageFilterKeyword($y, $image['info']['height'], $data['height']);
        if(!$newimage = self::imageCrop($image, $x, $y, $data['width'], $data['height'])) {
            return false;
        }

        return $newimage;
    }

    /**
     * Desaturates an image (to grayscale)
     * @return the grayscale image or false on failure
     */
    static private function imageDesaturate($image)
    {
        if (!(function_exists('imagefilter') && imagefilter($image['resource'], IMG_FILTER_GRAYSCALE))) {
            return false;
        }
        return $image;
    }

    static private function imageDesaturateEffect(&$image, $data)
    {
        if(!$newimage = self::imageDesaturate($image))
        {
            return false;
        }
        return $newimage;
    }

    static private function imageRotate($image, $degrees, $background = NULL)
    {
        if (!function_exists('imagerotate')) {
            return FALSE;
        }

        $width = $image['info']['width'];
        $height = $image['info']['height'];

        // Convert the hexadecimal background value to a color index value.
        if (isset($background)) {
            $rgb = array();
            for ($i = 16; $i >= 0; $i -= 8) {
                $rgb[] = (($background >> $i) & 0xFF);
            }
            $background = imagecolorallocatealpha($image['resource'], $rgb[0], $rgb[1], $rgb[2], 0);
        }
        // Set the background color as transparent if $background is NULL.
        else {
            // Get the current transparent color.
            $background = imagecolortransparent($image['resource']);

            // If no transparent colors, use white.
            if ($background == 0) {
                $background = imagecolorallocatealpha($image['resource'], 255, 255, 255, 0);
            }
        }

        // Images are assigned a new color palette when rotating, removing any
        // transparency flags. For GIF images, keep a record of the transparent color.
        if ($image['info']['extension'] == 'gif') {
            $transparent_index = imagecolortransparent($image['resource']);
            if ($transparent_index != 0) {
                $transparent_gif_color = imagecolorsforindex($image['resource'], $transparent_index);
            }
        }

        $newimage = $image;
        $newimage['resource'] = imagerotate($image['resource'], 360 - $degrees, $background);

        // GIFs need to reassign the transparent color after performing the rotate.
        if (isset($transparent_gif_color)) {
            $background = imagecolorexactalpha($newimage['resource'], $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
            imagecolortransparent($newimage['resource'], $background);
        }

        $newimage['info']['width'] = imagesx($newimage['resource']);
        $newimage['info']['height'] = imagesy($newimage['resource']);
        return $newimage;
    }

    static private function imageRotateEffect(&$image, $data)
    {
        $data += array(
            'degrees' => 0,
            'bgcolor' => NULL,
            'random' => FALSE,
        );

        // Convert short #FFF syntax to full #FFFFFF syntax.
        if (strlen($data['bgcolor']) == 4) {
            $c = $data['bgcolor'];
            $data['bgcolor'] = $c[0] . $c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3];
        }

        // Convert #FFFFFF syntax to hexadecimal colors.
        if ($data['bgcolor'] != '') {
            $data['bgcolor'] = hexdec(str_replace('#', '0x', $data['bgcolor']));
        }
        else {
            $data['bgcolor'] = NULL;
        }

        if (!empty($data['random'])) {
            $degrees = abs((float) $data['degrees']);
            $data['degrees'] = rand(-1 * $degrees, $degrees);
        }

        if(!$newimage = self::imageRotate($image, $data['degrees'], $data['bgcolor'])) {
            return false;
        }
        return $newimage;
    }


    /** Applies an effect to an image, based on the effect callback function defined
     *
     * @returns the modified image or false.
     */
    static private function imageEffectApply($image, $effect)
    {
        $function = 'image' . ucfirst(key($effect)) . 'Effect';
        if(method_exists(new ImageEffects(),$function)) {
            return self::$function($image, reset($effect)['info']);
        }
        return FALSE;
    }
}
