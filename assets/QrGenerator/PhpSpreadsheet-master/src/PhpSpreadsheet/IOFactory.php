<?php

namespace PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;


abstract class IOFactory
{
    public const READER_XLSX = 'Xlsx';
    public const READER_XLS = 'Xls';
    public const READER_XML = 'Xml';
    public const READER_ODS = 'Ods';
    public const READER_SYLK = 'Slk';
    public const READER_SLK = 'Slk';
    public const READER_GNUMERIC = 'Gnumeric';
    public const READER_HTML = 'Html';
    public const READER_CSV = 'Csv';

    public const WRITER_XLSX = 'Xlsx';
    public const WRITER_XLS = 'Xls';
    public const WRITER_ODS = 'Ods';
    public const WRITER_CSV = 'Csv';
    public const WRITER_HTML = 'Html';

    
    private static array $readers = [
        self::READER_XLSX => Reader\Xlsx::class,
        self::READER_XLS => Reader\Xls::class,
        self::READER_XML => Reader\Xml::class,
        self::READER_ODS => Reader\Ods::class,
        self::READER_SLK => Reader\Slk::class,
        self::READER_GNUMERIC => Reader\Gnumeric::class,
        self::READER_HTML => Reader\Html::class,
        self::READER_CSV => Reader\Csv::class,
    ];

    
    private static array $writers = [
        self::WRITER_XLS => Writer\Xls::class,
        self::WRITER_XLSX => Writer\Xlsx::class,
        self::WRITER_ODS => Writer\Ods::class,
        self::WRITER_CSV => Writer\Csv::class,
        self::WRITER_HTML => Writer\Html::class,
        'Tcpdf' => Writer\Pdf\Tcpdf::class,
        'Dompdf' => Writer\Pdf\Dompdf::class,
        'Mpdf' => Writer\Pdf\Mpdf::class,
    ];

    
    public static function createWriter(Spreadsheet $spreadsheet, string $writerType): IWriter
    {
        
        $className = $writerType;
        if (!in_array($writerType, self::$writers, true)) {
            if (!isset(self::$writers[$writerType])) {
                throw new Writer\Exception("No writer found for type $writerType");
            }

            
            $className = self::$writers[$writerType];
        }

        return new $className($spreadsheet);
    }

    
    public static function createReader(string $readerType): IReader
    {
        
        $className = $readerType;
        if (!in_array($readerType, self::$readers, true)) {
            if (!isset(self::$readers[$readerType])) {
                throw new Reader\Exception("No reader found for type $readerType");
            }

            
            $className = self::$readers[$readerType];
        }

        return new $className();
    }

    
    public static function load(string $filename, int $flags = 0, ?array $readers = null): Spreadsheet
    {
        $reader = self::createReaderForFile($filename, $readers);

        return $reader->load($filename, $flags);
    }

    
    public static function identify(string $filename, ?array $readers = null, bool $fullClassName = false): string
    {
        $reader = self::createReaderForFile($filename, $readers);
        $className = $reader::class;
        if ($fullClassName) {
            return $className;
        }
        $classType = explode('\\', $className);

        return array_pop($classType);
    }

    
    public static function createReaderForFile(string $filename, ?array $readers = null): IReader
    {
        File::assertFile($filename);

        $testReaders = self::$readers;
        if ($readers !== null) {
            $readers = array_map('strtoupper', $readers);
            $testReaders = array_filter(
                self::$readers,
                fn (string $readerType): bool => in_array(strtoupper($readerType), $readers, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        
        $guessedReader = self::getReaderTypeFromExtension($filename);
        if (($guessedReader !== null) && array_key_exists($guessedReader, $testReaders)) {
            $reader = self::createReader($guessedReader);

            
            if ($reader->canRead($filename)) {
                return $reader;
            }
        }

        
        
        foreach ($testReaders as $readerType => $class) {
            
            if ($readerType !== $guessedReader) {
                $reader = self::createReader($readerType);
                if ($reader->canRead($filename)) {
                    return $reader;
                }
            }
        }

        throw new Reader\Exception('Unable to identify a reader for this file');
    }

    
    private static function getReaderTypeFromExtension(string $filename): ?string
    {
        $pathinfo = pathinfo($filename);
        if (!isset($pathinfo['extension'])) {
            return null;
        }

        return match (strtolower($pathinfo['extension'])) {
            
            'xlsx',
            
            'xlsm',
            
            'xltx',
            
            'xltm' => 'Xlsx',
            
            'xls',
            
            'xlt' => 'Xls',
            
            'ods',
            
            'ots' => 'Ods',
            'slk' => 'Slk',
            
            'xml' => 'Xml',
            'gnumeric' => 'Gnumeric',
            'htm', 'html' => 'Html',
            
            
            
            'csv' => null,
            default => null,
        };
    }

    
    public static function registerWriter(string $writerType, string $writerClass): void
    {
        if (!is_a($writerClass, IWriter::class, true)) {
            throw new Writer\Exception('Registered writers must implement ' . IWriter::class);
        }

        self::$writers[$writerType] = $writerClass;
    }

    
    public static function registerReader(string $readerType, string $readerClass): void
    {
        if (!is_a($readerClass, IReader::class, true)) {
            throw new Reader\Exception('Registered readers must implement ' . IReader::class);
        }

        self::$readers[$readerType] = $readerClass;
    }
}
