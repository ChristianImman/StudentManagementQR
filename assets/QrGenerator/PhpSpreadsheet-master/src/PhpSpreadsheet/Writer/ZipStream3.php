<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use ZipStream\ZipStream;

class ZipStream3
{
    
    public static function newZipStream($fileHandle): ZipStream
    {
        return new ZipStream(
            enableZip64: false,
            outputStream: $fileHandle,
            sendHttpHeaders: false,
            defaultEnableZeroHeader: false,
        );
    }
}
