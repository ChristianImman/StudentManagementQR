<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use ZipArchive;

class Drawing extends BaseDrawing
{
    const IMAGE_TYPES_CONVERTION_MAP = [
        IMAGETYPE_GIF => IMAGETYPE_PNG,
        IMAGETYPE_JPEG => IMAGETYPE_JPEG,
        IMAGETYPE_PNG => IMAGETYPE_PNG,
        IMAGETYPE_BMP => IMAGETYPE_PNG,
    ];

    
    private string $path;

    
    private bool $isUrl;

    
    public function __construct()
    {
        
        $this->path = '';
        $this->isUrl = false;

        
        parent::__construct();
    }

    
    public function getFilename(): string
    {
        return basename($this->path);
    }

    
    public function getIndexedFilename(): string
    {
        return md5($this->path) . '.' . $this->getExtension();
    }

    
    public function getExtension(): string
    {
        $exploded = explode('.', basename($this->path));

        return $exploded[count($exploded) - 1];
    }

    
    public function getMediaFilename(): string
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        return sprintf('image%d%s', $this->getImageIndex(), $this->getImageFileExtensionForSave());
    }

    
    public function getPath(): string
    {
        return $this->path;
    }

    
    public function setPath(string $path, bool $verifyFile = true, ?ZipArchive $zip = null): static
    {
        $this->isUrl = false;
        if (preg_match('~^data:image/[a-z]+;base64,~', $path) === 1) {
            $this->path = $path;

            return $this;
        }

        $this->path = '';
        
        if (filter_var($path, FILTER_VALIDATE_URL) || (preg_match('/^([\w\s\x00-\x1f]+):/u', $path) && !preg_match('/^([\w]+):/u', $path))) {
            if (!preg_match('/^(http|https|file|ftp|s3):/', $path)) {
                throw new PhpSpreadsheetException('Invalid protocol for linked drawing');
            }
            
            $this->isUrl = true;
            $ctx = null;
            
            
            if (str_starts_with($path, 'https:') || str_starts_with($path, 'http:')) {
                $ctxArray = [
                    'http' => [
                        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                        'header' => [
                            
                            'Accept: image*;q=0.8',
                        ],
                    ],
                ];
                if (str_starts_with($path, 'https:')) {
                    $ctxArray['ssl'] = ['crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT];
                }
                $ctx = stream_context_create($ctxArray);
            }
            $imageContents = @file_get_contents($path, false, $ctx);
            if ($imageContents !== false) {
                $filePath = tempnam(sys_get_temp_dir(), 'Drawing');
                if ($filePath) {
                    $put = @file_put_contents($filePath, $imageContents);
                    if ($put !== false) {
                        if ($this->isImage($filePath)) {
                            $this->path = $path;
                            $this->setSizesAndType($filePath);
                        }
                        unlink($filePath);
                    }
                }
            }
        } elseif ($zip instanceof ZipArchive) {
            $zipPath = explode('
            $locate = @$zip->locateName($zipPath);
            if ($locate !== false) {
                if ($this->isImage($path)) {
                    $this->path = $path;
                    $this->setSizesAndType($path);
                }
            }
        } else {
            $exists = @file_exists($path);
            if ($exists !== false && $this->isImage($path)) {
                $this->path = $path;
                $this->setSizesAndType($path);
            }
        }
        if ($this->path === '' && $verifyFile) {
            throw new PhpSpreadsheetException("File $path not found!");
        }

        if ($this->worksheet !== null) {
            if ($this->path !== '') {
                $this->worksheet->getCell($this->coordinates);
            }
        }

        return $this;
    }

    private function isImage(string $path): bool
    {
        $mime = (string) @mime_content_type($path);
        $retVal = false;
        if (str_starts_with($mime, 'image/')) {
            $retVal = true;
        } elseif ($mime === 'application/octet-stream') {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $retVal = in_array($extension, ['bin', 'emf'], true);
        }

        return $retVal;
    }

    
    public function getIsURL(): bool
    {
        return $this->isUrl;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->path
            . parent::getHashCode()
            . __CLASS__
        );
    }

    
    public function getImageTypeForSave(): int
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        return self::IMAGE_TYPES_CONVERTION_MAP[$this->type];
    }

    
    public function getImageFileExtensionForSave(bool $includeDot = true): string
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        $result = image_type_to_extension(self::IMAGE_TYPES_CONVERTION_MAP[$this->type], $includeDot);

        return "$result";
    }

    
    public function getImageMimeType(): string
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        return image_type_to_mime_type(self::IMAGE_TYPES_CONVERTION_MAP[$this->type]);
    }
}
