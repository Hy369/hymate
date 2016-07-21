<?php
/*
 * This file is part of the Hymate package.
 *
 * (c) Hylin Yin <hylin@iphp8.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hymate\Thumbnail;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides basic utility to generate the thumbnail.
 *
 * @package Hymate\Thumbnail
 * @author Hylin Yin <hylin@iphp8.com>
 */
class Thumbnail
{
    protected $debug;
    /** @var  Filesystem */
    protected $filesystem;
    protected $thumbnailPath;
    protected $thumbnailUrl;
    protected $imagePath;
    protected $imageWidth;
    protected $imageHeight;
    protected $imagetype;
    protected $thumbnailWidth;
    protected $thumbnailHeight;
    protected $allowedImagetypeMapping;
    protected $fillColor;

    /**
     * Thumbnail constructor.
     *
     * @param string $thumbnailPath
     * @param string $thumbnailUrl
     */
    public function __construct($thumbnailPath, $thumbnailUrl)
    {
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($thumbnailPath);
        $this->thumbnailPath = realpath($thumbnailPath) . '/';

        $this->thumbnailUrl = trim($thumbnailUrl, '/') . '/';

        $this->debug = false;
        $this->thumbnailWidth = 300;
        $this->thumbnailHeight = 180;
        $this->allowedImagetypeMapping = array(
            '1' => 'gif',
            '2' => 'jpg',
            '3' => 'png',
            '4' => 'swf',
            '5' => 'psd',
            '6' => 'bmp',
            '15' => 'wbmp',
        );
        $this->fillColor = array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0);
        $this->imageWidth = $this->imageHeight = $this->imagetype = null;
    }

    /**
     * Whether to allow debugging mode
     *
     * @param bool $isDebug
     * @return Thumbnail
     */
    public function setDebug($isDebug)
    {
        $this->debug = (bool)$isDebug;

        return $this;
    }

    /**
     * Set the size of thumbnail.
     *
     * @param int $width
     * @param int $height
     * @return Thumbnail
     */
    public function setThumbnail($width, $height)
    {
        $this->thumbnailWidth = intval(abs($width));
        $this->thumbnailHeight = intval(abs($height));

        return $this;
    }

    /**
     * Set the image path.
     *
     * @param $src
     * @return Thumbnail
     */
    public function setImage($src)
    {
        $this->imagePath = $src;
        $this->getImageInfo();

        return $this;
    }

    /**
     * Gets the image infomation(imagetype, width, height).
     *
     * @return bool
     */
    private function getImageInfo()
    {
        if (!file_exists($this->imagePath)) {
            $this->debug(sprintf('The file "%s" does not existed.', $this->imagePath));
            return false;
        }
        $imageInfo = @getimagesize($this->imagePath);
        if (false === $imageInfo) {
            $this->debug(sprintf('The file type must be %s.', join(', ', $this->allowedImagetypeMapping)));
            return false;
        }
        $this->imagetype = $this->getImagetype($imageInfo[2]);
        $this->imageWidth = $imageInfo[0];
        $this->imageHeight = $imageInfo[1];
        return true;
    }

    /**
     * Sets the fill color.
     *
     * @param string $color Colors in Hex
     * @param int $alpha
     * @return Thumbnail|bool
     * @throws \Exception
     */
    public function setFillColor($color, $alpha = 0)
    {
        $color = ltrim($color, '#');
        if (6 !== strlen($color)) {
            throw new \Exception(sprintf('Invalid color format: %s', $color));
        }
        $color  = str_split($color, 2);
        $this->fillColor['red'] = hexdec($color[0]);
        $this->fillColor['green'] = hexdec($color[1]);
        $this->fillColor['blue'] = hexdec($color[2]);
        $this->fillColor['alpha'] = abs($alpha) < 127 ? abs($alpha) : 127;

        return $this;
    }

    /**
     * Use rgba to set the fill color.
     *
     * @param $red
     * @param $green
     * @param $blue
     * @param int $alpha
     * @return Thumbnail
     */
    public function setFillColorRgba($red, $green, $blue, $alpha = 0)
    {
        $this->fillColor['red'] = abs($red) < 255 ? intval(abs($red)) : 255;
        $this->fillColor['green'] = abs($green) < 255 ? intval(abs($green)) : 255;
        $this->fillColor['blue'] = abs($blue) < 255 ? intval(abs($blue)) : 255;
        $this->fillColor['alpha'] = abs($alpha) < 127 ? abs($alpha) : 127;

        return $this;
    }

    /**
     * Starts to create the thumbnail.
     *
     * @return string|false
     */
    public function create()
    {
        if (is_null($this->imageWidth) || is_null($this->imageHeight) || is_null($this->imagetype)) {
            return false;
        }

        $thumbnailName = md5($this->imagePath) . '.png';

        if (file_exists($this->thumbnailPath . $thumbnailName) && !$this->debug) {
            return $this->thumbnailUrl . $thumbnailName;
        }

        $imageHandler = imagecreatetruecolor($this->thumbnailWidth, $this->thumbnailHeight);
        $imagecolor = imagecolorallocatealpha($imageHandler, $this->fillColor['red'], $this->fillColor['green'], $this->fillColor['blue'], $this->fillColor['alpha']);
        imagealphablending($imageHandler, false);
        imagefill($imageHandler, 0, 0, $imagecolor);
        imagesavealpha($imageHandler, true);

        $dstImage = $this->readImage();
        $imageHandler = $this->resampledImage($imageHandler, $dstImage);

        imagepng($imageHandler, $this->thumbnailPath . $thumbnailName);

        return $this->thumbnailUrl . $thumbnailName;
    }

    /**
     * Validate the imagetype.
     *
     * @param int $index
     * @return string|bool
     */
    protected function getImagetype($index)
    {
        if (array_key_exists($index, $this->allowedImagetypeMapping)) {
            return $this->allowedImagetypeMapping[$index];
        } else {
            $this->debug(sprintf('The file type must be %s.', join(', ', $this->allowedImagetypeMapping)));
            return false;
        }
    }

    /**
     * Is enable the debug.
     *
     * @param string $msg
     * @return bool
     * @throws \Exception
     */
    protected function debug($msg)
    {
        if ($this->debug) {
            throw new \Exception($msg);
        }
    }

    /**
     * Read the image as resource.
     *
     * @return resource
     */
    protected function readImage()
    {
        $data = '';
        if (function_exists('file_get_contents')) {
            $data = file_get_contents($this->imagePath);
        } else {
            $handle = fopen($this->imagePath, 'r');
            while (!feof($handle)) {
                $data .= fgets($handle, 4096);
            }
            fclose($handle);
        }
        return @imagecreatefromstring($data);
    }

    /**
     * Copy and resize resource image.
     *
     * @param resource $dstImage
     * @param resource $srcImage
     * @return resource
     */
    protected function resampledImage($dstImage, $srcImage)
    {
        $srcX = $srcY = 0;
        list($dstWidth, $dstHeight) = $this->getUniformScale(
            $this->thumbnailWidth,
            $this->thumbnailHeight,
            $this->imageWidth,
            $this->imageHeight
        );
        list($dstX, $dstY) = $this->getCoordinate($this->thumbnailWidth, $this->thumbnailHeight, $dstWidth, $dstHeight);

        imagecopyresampled($dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $this->imageWidth, $this->imageHeight);

        return $dstImage;
    }

    /**
     * Get the uniform scale of resource in destination.
     *
     * @param int $dstWidth
     * @param int $dstHeight
     * @param int $srcWidth
     * @param int $srcHeight
     * @param bool $onlyScaleDown
     * @return array
     */
    protected function getUniformScale($dstWidth, $dstHeight, $srcWidth, $srcHeight, $onlyScaleDown = true)
    {
        if ($onlyScaleDown && $dstWidth >= $srcWidth && $dstHeight >= $srcHeight) {
            $scaleWidth = $srcWidth;
            $scaleHeight = $srcHeight;
        } else {
            $scaleWidth = $dstWidth;
            $scaleHeight = $srcHeight * ($dstWidth / $srcWidth);
            if ($scaleHeight > $dstHeight) {
                $scaleWidth = $srcWidth * ($dstHeight / $srcHeight);
                $scaleHeight = $dstHeight;
            }
        }

        return array($scaleWidth, $scaleHeight);
    }

    /**
     * Get the coordinates of resource in destination.
     *
     * @param int $dstWidth
     * @param int $dstHeight
     * @param int $srcWidth
     * @param int $srcHeight
     * @param string $horizontal
     * @param string $vertical
     * @return array
     */
    protected function getCoordinate($dstWidth, $dstHeight, $srcWidth, $srcHeight, $horizontal = 'center',  $vertical = 'bottom')
    {
        switch (strtolower($horizontal)) {
            case 'left':
                $coordX = 0;
                break;
            case 'right':
                $coordX = $dstWidth - $srcWidth;
                break;
            case 'center':
            default:
                $coordX = ($dstWidth - $srcWidth) / 2;
        }

        switch (strtolower($vertical)) {
            case 'top':
                $coordY = 0;
                break;
            case 'center':
                $coordY = ($dstHeight - $srcHeight) / 2;
                break;
            case 'bottom':
            default:
                $coordY = $dstHeight - $srcHeight;
        }

        return array($coordX, $coordY);
    }
}