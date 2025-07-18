<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class XMLWriter extends \XMLWriter
{
    public static bool $debugEnabled = false;

    
    const STORAGE_MEMORY = 1;
    const STORAGE_DISK = 2;

    
    private string $tempFileName = '';

    
    public function __construct(int $temporaryStorage = self::STORAGE_MEMORY, ?string $temporaryStorageFolder = null)
    {
        
        if ($temporaryStorage == self::STORAGE_MEMORY) {
            $this->openMemory();
        } else {
            
            if ($temporaryStorageFolder === null) {
                $temporaryStorageFolder = File::sysGetTempDir();
            }
            $this->tempFileName = (string) @tempnam($temporaryStorageFolder, 'xml');

            
            if (empty($this->tempFileName) || $this->openUri($this->tempFileName) === false) {
                
                $this->openMemory();
            }
        }

        
        if (self::$debugEnabled) {
            $this->setIndent(true);
        }
    }

    
    public function __destruct()
    {
        
        
        if ($this->tempFileName != '') {
            @unlink($this->tempFileName);
        }
    }

    public function __wakeup(): void
    {
        $this->tempFileName = '';

        throw new SpreadsheetException('Unserialize not permitted');
    }

    
    public function getData(): string
    {
        if ($this->tempFileName == '') {
            return $this->outputMemory(true);
        }
        $this->flush();

        return file_get_contents($this->tempFileName) ?: '';
    }

    
    public function writeRawData($rawTextData): bool
    {
        if (is_array($rawTextData)) {
            $rawTextData = implode("\n", $rawTextData);
        }

        return $this->writeRaw(htmlspecialchars($rawTextData ?? ''));
    }
}
