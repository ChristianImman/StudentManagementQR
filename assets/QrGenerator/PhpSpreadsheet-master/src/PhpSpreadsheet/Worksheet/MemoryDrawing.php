<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use GdImage;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Shared\File;

class MemoryDrawing extends BaseDrawing
{
    
    const RENDERING_DEFAULT = 'imagepng';
    const RENDERING_PNG = 'imagepng';
    const RENDERING_GIF = 'imagegif';
    const RENDERING_JPEG = 'imagejpeg';

    
    const MIMETYPE_DEFAULT = 'image/png';
    const MIMETYPE_PNG = 'image/png';
    const MIMETYPE_GIF = 'image/gif';
    const MIMETYPE_JPEG = 'image/jpeg';

    const SUPPORTED_MIME_TYPES = [
        self::MIMETYPE_GIF,
        self::MIMETYPE_JPEG,
        self::MIMETYPE_PNG,
    ];

    
    private null|GdImage $imageResource = null;

    
    private string $renderingFunction;

    
    private string $mimeType;

    
    private string $uniqueName;

    
    public function __construct()
    {
        
        $this->renderingFunction = self::RENDERING_DEFAULT;
        $this->mimeType = self::MIMETYPE_DEFAULT;
        $this->uniqueName = md5(mt_rand(0, 9999) . time() . mt_rand(0, 9999));

        
        parent::__construct();
    }

    public function __destruct()
    {
        if ($this->imageResource) {
            @imagedestroy($this->imageResource);
            $this->imageResource = null;
        }
        $this->worksheet = null;
    }

    public function __clone()
    {
        parent::__clone();
        $this->cloneResource();
    }

    private function cloneResource(): void
    {
        if (!$this->imageResource) {
            return;
        }

        $width = (int) imagesx($this->imageResource);
        $height = (int) imagesy($this->imageResource);

        if (imageistruecolor($this->imageResource)) {
            $clone = imagecreatetruecolor($width, $height);
            if (!$clone) {
                throw new Exception('Could not clone image resource');
            }

            imagealphablending($clone, false);
            imagesavealpha($clone, true);
        } else {
            $clone = imagecreate($width, $height);
            if (!$clone) {
                throw new Exception('Could not clone image resource');
            }

            
            $transparent = imagecolortransparent($this->imageResource);
            if ($transparent >= 0) {
                
                $rgb = imagecolorsforindex($this->imageResource, $transparent);

                imagesavealpha($clone, true);
                $color = imagecolorallocatealpha($clone, $rgb['red'], $rgb['green'], $rgb['blue'], $rgb['alpha']);
                if ($color === false) {
                    throw new Exception('Could not get image alpha color');
                }

                imagefill($clone, 0, 0, $color);
            }
        }

        
        imagecopy($clone, $this->imageResource, 0, 0, 0, 0, $width, $height);

        $this->imageResource = $clone;
    }

    
    public static function fromStream($imageStream): self
    {
        $streamValue = stream_get_contents($imageStream);
        if ($streamValue === false) {
            throw new Exception('Unable to read data from stream');
        }

        return self::fromString($streamValue);
    }

    
    public static function fromString(string $imageString): self
    {
        $gdImage = @imagecreatefromstring($imageString);
        if ($gdImage === false) {
            throw new Exception('Value cannot be converted to an image');
        }

        $mimeType = self::identifyMimeType($imageString);
        if (imageistruecolor($gdImage) || imagecolortransparent($gdImage) >= 0) {
            imagesavealpha($gdImage, true);
        }
        $renderingFunction = self::identifyRenderingFunction($mimeType);

        $drawing = new self();
        $drawing->setImageResource($gdImage);
        $drawing->setRenderingFunction($renderingFunction);
        $drawing->setMimeType($mimeType);

        return $drawing;
    }

    
    private static function identifyRenderingFunction(string $mimeType): string
    {
        return match ($mimeType) {
            self::MIMETYPE_PNG => self::RENDERING_PNG,
            self::MIMETYPE_JPEG => self::RENDERING_JPEG,
            self::MIMETYPE_GIF => self::RENDERING_GIF,
            default => self::RENDERING_DEFAULT,
        };
    }

    
    private static function identifyMimeType(string $imageString): string
    {
        $temporaryFileName = File::temporaryFilename();
        file_put_contents($temporaryFileName, $imageString);

        $mimeType = self::identifyMimeTypeUsingExif($temporaryFileName);
        if ($mimeType !== null) {
            unlink($temporaryFileName);

            return $mimeType;
        }

        $mimeType = self::identifyMimeTypeUsingGd($temporaryFileName);
        if ($mimeType !== null) {
            unlink($temporaryFileName);

            return $mimeType;
        }

        unlink($temporaryFileName);

        return self::MIMETYPE_DEFAULT;
    }

    private static function identifyMimeTypeUsingExif(string $temporaryFileName): ?string
    {
        if (function_exists('exif_imagetype')) {
            $imageType = @exif_imagetype($temporaryFileName);
            $mimeType = ($imageType) ? image_type_to_mime_type($imageType) : null;

            return self::supportedMimeTypes($mimeType);
        }

        return null;
    }

    private static function identifyMimeTypeUsingGd(string $temporaryFileName): ?string
    {
        if (function_exists('getimagesize')) {
            $imageSize = @getimagesize($temporaryFileName);
            if (is_array($imageSize)) {
                $mimeType = $imageSize['mime'];

                return self::supportedMimeTypes($mimeType);
            }
        }

        return null;
    }

    private static function supportedMimeTypes(?string $mimeType = null): ?string
    {
        if (in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return $mimeType;
        }

        return null;
    }

    
    public function getImageResource(): ?GdImage
    {
        return $this->imageResource;
    }

    
    public function setImageResource(?GdImage $value): static
    {
        $this->imageResource = $value;

        if ($this->imageResource !== null) {
            
            $this->width = (int) imagesx($this->imageResource);
            $this->height = (int) imagesy($this->imageResource);
        }

        return $this;
    }

    
    public function getRenderingFunction(): string
    {
        return $this->renderingFunction;
    }

    
    public function setRenderingFunction(string $value): static
    {
        $this->renderingFunction = $value;

        return $this;
    }

    
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    
    public function setMimeType(string $value): static
    {
        $this->mimeType = $value;

        return $this;
    }

    
    public function getIndexedFilename(): string
    {
        $extension = strtolower($this->getMimeType());
        $extension = explode('/', $extension);
        $extension = $extension[1];

        return $this->uniqueName . $this->getImageIndex() . '.' . $extension;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->renderingFunction
            . $this->mimeType
            . $this->uniqueName
            . parent::getHashCode()
            . __CLASS__
        );
    }
}
