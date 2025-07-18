<?php

namespace PhpOffice\PhpSpreadsheet\Helper;

use PhpOffice\PhpSpreadsheet\Exception;


class Downloader
{
    protected string $filepath;

    protected string $filename;

    protected string $filetype;

    protected const CONTENT_TYPES = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'csv' => 'text/csv',
        'html' => 'text/html',
        'pdf' => 'application/pdf',
    ];

    public function __construct(string $folder, string $filename, ?string $filetype = null)
    {
        if ((is_dir($folder) === false) || (is_readable($folder) === false)) {
            throw new Exception('Folder is not accessible');
        }
        $filepath = "{$folder}/{$filename}";
        $this->filepath = (string) realpath($filepath);
        $this->filename = basename($filepath);
        clearstatcache();
        if ((is_file($this->filepath) === false) || (is_readable($this->filepath) === false)) {
            throw new Exception('File not found, or not a regular file, or cannot be read');
        }

        $filetype ??= pathinfo($filename, PATHINFO_EXTENSION);
        if (array_key_exists(strtolower($filetype), self::CONTENT_TYPES) === false) {
            throw new Exception('Invalid filetype: file cannot be downloaded');
        }
        $this->filetype = strtolower($filetype);
    }

    public function download(): void
    {
        $this->headers();

        readfile($this->filepath);
    }

    public function headers(): void
    {
        
        
        
        
        if ((int) ob_get_length() > 0) {
            ob_clean();
        }

        $this->contentType();
        $this->contentDisposition();
        $this->cacheHeaders();
        $this->fileSize();

        flush();
    }

    protected function contentType(): void
    {
        header('Content-Type: ' . self::CONTENT_TYPES[$this->filetype]);
    }

    protected function contentDisposition(): void
    {
        header('Content-Disposition: attachment;filename="' . $this->filename . '"');
    }

    protected function cacheHeaders(): void
    {
        header('Cache-Control: max-age=0');
        
        header('Cache-Control: max-age=1');

        
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); 
        header('Cache-Control: cache, must-revalidate'); 
        header('Pragma: public'); 
    }

    protected function fileSize(): void
    {
        header('Content-Length: ' . filesize($this->filepath));
    }
}
