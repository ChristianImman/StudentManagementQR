<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv\Delimiter;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class Csv extends BaseReader
{
    const DEFAULT_FALLBACK_ENCODING = 'CP1252';
    const GUESS_ENCODING = 'guess';
    const UTF8_BOM = "\xEF\xBB\xBF";
    const UTF8_BOM_LEN = 3;
    const UTF16BE_BOM = "\xfe\xff";
    const UTF16BE_BOM_LEN = 2;
    const UTF16BE_LF = "\x00\x0a";
    const UTF16LE_BOM = "\xff\xfe";
    const UTF16LE_BOM_LEN = 2;
    const UTF16LE_LF = "\x0a\x00";
    const UTF32BE_BOM = "\x00\x00\xfe\xff";
    const UTF32BE_BOM_LEN = 4;
    const UTF32BE_LF = "\x00\x00\x00\x0a";
    const UTF32LE_BOM = "\xff\xfe\x00\x00";
    const UTF32LE_BOM_LEN = 4;
    const UTF32LE_LF = "\x0a\x00\x00\x00";

    
    private string $inputEncoding = 'UTF-8';

    
    private string $fallbackEncoding = self::DEFAULT_FALLBACK_ENCODING;

    
    private ?string $delimiter = null;

    
    private string $enclosure = '"';

    
    private int $sheetIndex = 0;

    
    private bool $contiguous = false;

    
    private ?string $escapeCharacter = null;

    
    private static $constructorCallback;

    
    public const DEFAULT_TEST_AUTODETECT = false;

    
    private bool $testAutodetect = self::DEFAULT_TEST_AUTODETECT;

    protected bool $castFormattedNumberToNumeric = false;

    protected bool $preserveNumericFormatting = false;

    private bool $preserveNullString = false;

    private bool $sheetNameIsFileName = false;

    private string $getTrue = 'true';

    private string $getFalse = 'false';

    private string $thousandsSeparator = ',';

    private string $decimalSeparator = '.';

    
    public function __construct()
    {
        parent::__construct();
        $callback = self::$constructorCallback;
        if ($callback !== null) {
            $callback($this);
        }
    }

    
    public static function setConstructorCallback(?callable $callback): void
    {
        self::$constructorCallback = $callback;
    }

    public static function getConstructorCallback(): ?callable
    {
        return self::$constructorCallback;
    }

    public function setInputEncoding(string $encoding): self
    {
        $this->inputEncoding = $encoding;

        return $this;
    }

    public function getInputEncoding(): string
    {
        return $this->inputEncoding;
    }

    public function setFallbackEncoding(string $fallbackEncoding): self
    {
        $this->fallbackEncoding = $fallbackEncoding;

        return $this;
    }

    public function getFallbackEncoding(): string
    {
        return $this->fallbackEncoding;
    }

    
    protected function skipBOM(): void
    {
        rewind($this->fileHandle);

        if (fgets($this->fileHandle, self::UTF8_BOM_LEN + 1) !== self::UTF8_BOM) {
            rewind($this->fileHandle);
        }
    }

    
    protected function checkSeparator(): void
    {
        $line = fgets($this->fileHandle);
        if ($line === false) {
            return;
        }

        if ((strlen(trim($line, "\r\n")) == 5) && (stripos($line, 'sep=') === 0)) {
            $this->delimiter = substr($line, 4, 1);

            return;
        }

        $this->skipBOM();
    }

    
    protected function inferSeparator(): void
    {
        if ($this->delimiter !== null) {
            return;
        }

        $inferenceEngine = new Delimiter($this->fileHandle, $this->getEscapeCharacter(), $this->enclosure);

        
        if ($inferenceEngine->linesCounted() === 0) {
            $this->delimiter = $inferenceEngine->getDefaultDelimiter();
            $this->skipBOM();

            return;
        }

        $this->delimiter = $inferenceEngine->infer();

        
        if ($this->delimiter === null) {
            $this->delimiter = $inferenceEngine->getDefaultDelimiter();
        }

        $this->skipBOM();
    }

    
    public function listWorksheetInfo(string $filename): array
    {
        
        $this->openFileOrMemory($filename);
        $fileHandle = $this->fileHandle;

        
        $this->skipBOM();
        $this->checkSeparator();
        $this->inferSeparator();

        
        $worksheetInfo = [];
        $worksheetInfo[0]['worksheetName'] = 'Worksheet';
        $worksheetInfo[0]['lastColumnLetter'] = 'A';
        $worksheetInfo[0]['lastColumnIndex'] = 0;
        $worksheetInfo[0]['totalRows'] = 0;
        $worksheetInfo[0]['totalColumns'] = 0;
        $delimiter = $this->delimiter ?? '';

        
        $rowData = self::getCsv($fileHandle, 0, $delimiter, $this->enclosure, $this->escapeCharacter);
        while (is_array($rowData)) {
            ++$worksheetInfo[0]['totalRows'];
            $worksheetInfo[0]['lastColumnIndex'] = max($worksheetInfo[0]['lastColumnIndex'], count($rowData) - 1);
            $rowData = self::getCsv($fileHandle, 0, $delimiter, $this->enclosure, $this->escapeCharacter);
        }

        $worksheetInfo[0]['lastColumnLetter'] = Coordinate::stringFromColumnIndex($worksheetInfo[0]['lastColumnIndex'] + 1);
        $worksheetInfo[0]['totalColumns'] = $worksheetInfo[0]['lastColumnIndex'] + 1;
        $worksheetInfo[0]['sheetState'] = Worksheet::SHEETSTATE_VISIBLE;

        
        fclose($fileHandle);

        return $worksheetInfo;
    }

    
    protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
        $spreadsheet = $this->newSpreadsheet();
        $spreadsheet->setValueBinder($this->valueBinder);

        
        return $this->loadIntoExisting($filename, $spreadsheet);
    }

    
    public function loadSpreadsheetFromString(string $contents): Spreadsheet
    {
        $spreadsheet = $this->newSpreadsheet();
        $spreadsheet->setValueBinder($this->valueBinder);

        
        return $this->loadStringOrFile('data://text/plain,' . urlencode($contents), $spreadsheet, true);
    }

    private function openFileOrMemory(string $filename): void
    {
        
        $fhandle = $this->canRead($filename);
        if (!$fhandle) {
            throw new ReaderException($filename . ' is an Invalid Spreadsheet file.');
        }
        if ($this->inputEncoding === 'UTF-8') {
            $encoding = self::guessEncodingBom($filename);
            if ($encoding !== '') {
                $this->inputEncoding = $encoding;
            }
        }
        if ($this->inputEncoding === self::GUESS_ENCODING) {
            $this->inputEncoding = self::guessEncoding($filename, $this->fallbackEncoding);
        }
        $this->openFile($filename);
        if ($this->inputEncoding !== 'UTF-8') {
            fclose($this->fileHandle);
            $entireFile = file_get_contents($filename);
            $fileHandle = fopen('php://memory', 'r+b');
            if ($fileHandle !== false && $entireFile !== false) {
                $this->fileHandle = $fileHandle;
                $data = StringHelper::convertEncoding($entireFile, 'UTF-8', $this->inputEncoding);
                fwrite($this->fileHandle, $data);
                $this->skipBOM();
            }
        }
    }

    public function setTestAutoDetect(bool $value): self
    {
        $this->testAutodetect = $value;

        return $this;
    }

    private function setAutoDetect(?string $value, int $version = PHP_VERSION_ID): ?string
    {
        $retVal = null;
        if ($value !== null && $this->testAutodetect && $version < 90000) {
            $retVal2 = @ini_set('auto_detect_line_endings', $value);
            if (is_string($retVal2)) {
                $retVal = $retVal2;
            }
        }

        return $retVal;
    }

    public function castFormattedNumberToNumeric(
        bool $castFormattedNumberToNumeric,
        bool $preserveNumericFormatting = false
    ): void {
        $this->castFormattedNumberToNumeric = $castFormattedNumberToNumeric;
        $this->preserveNumericFormatting = $preserveNumericFormatting;
    }

    
    private function openDataUri(string $filename): void
    {
        $fileHandle = fopen($filename, 'rb');
        if ($fileHandle === false) {
            
            throw new ReaderException('Could not open file ' . $filename . ' for reading.');
            
        }

        $this->fileHandle = $fileHandle;
    }

    
    public function loadIntoExisting(string $filename, Spreadsheet $spreadsheet): Spreadsheet
    {
        return $this->loadStringOrFile($filename, $spreadsheet, false);
    }

    
    private function loadStringOrFile(string $filename, Spreadsheet $spreadsheet, bool $dataUri): Spreadsheet
    {
        
        $iniset = $this->setAutoDetect('1');

        try {
            $this->loadStringOrFile2($filename, $spreadsheet, $dataUri);
            $this->setAutoDetect($iniset);
        } catch (Throwable $e) {
            $this->setAutoDetect($iniset);

            throw $e;
        }

        return $spreadsheet;
    }

    private function loadStringOrFile2(string $filename, Spreadsheet $spreadsheet, bool $dataUri): void
    {

        
        if ($dataUri) {
            $this->openDataUri($filename);
        } else {
            $this->openFileOrMemory($filename);
        }
        $fileHandle = $this->fileHandle;

        
        $this->skipBOM();
        $this->checkSeparator();
        $this->inferSeparator();

        
        while ($spreadsheet->getSheetCount() <= $this->sheetIndex) {
            $spreadsheet->createSheet();
        }
        $sheet = $spreadsheet->setActiveSheetIndex($this->sheetIndex);
        if ($this->sheetNameIsFileName) {
            $sheet->setTitle(substr(basename($filename, '.csv'), 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH));
        }

        
        $currentRow = 1;
        $outRow = 0;

        
        $delimiter = $this->delimiter ?? '';
        $rowData = self::getCsv($fileHandle, 0, $delimiter, $this->enclosure, $this->escapeCharacter);
        $valueBinder = $this->valueBinder ?? Cell::getValueBinder();
        $preserveBooleanString = method_exists($valueBinder, 'getBooleanConversion') && $valueBinder->getBooleanConversion();
        $this->getTrue = Calculation::getTRUE();
        $this->getFalse = Calculation::getFALSE();
        $this->thousandsSeparator = StringHelper::getThousandsSeparator();
        $this->decimalSeparator = StringHelper::getDecimalSeparator();
        while (is_array($rowData)) {
            $noOutputYet = true;
            $columnLetter = 'A';
            foreach ($rowData as $rowDatum) {
                if ($preserveBooleanString) {
                    $rowDatum = $rowDatum ?? '';
                } else {
                    $this->convertBoolean($rowDatum);
                }
                $numberFormatMask = $this->castFormattedNumberToNumeric ? $this->convertFormattedNumber($rowDatum) : '';
                if (($rowDatum !== '' || $this->preserveNullString) && $this->readFilter->readCell($columnLetter, $currentRow)) {
                    if ($this->contiguous) {
                        if ($noOutputYet) {
                            $noOutputYet = false;
                            ++$outRow;
                        }
                    } else {
                        $outRow = $currentRow;
                    }
                    
                    if ($numberFormatMask !== '') {
                        $sheet->getStyle($columnLetter . $outRow)
                            ->getNumberFormat()
                            ->setFormatCode($numberFormatMask);
                    }
                    
                    $sheet->getCell($columnLetter . $outRow)->setValue($rowDatum);
                }
                ++$columnLetter;
            }
            $rowData = self::getCsv($fileHandle, 0, $delimiter, $this->enclosure, $this->escapeCharacter);
            ++$currentRow;
        }

        
        fclose($fileHandle);
    }

    
    private function convertBoolean(mixed &$rowDatum): void
    {
        if (is_string($rowDatum)) {
            if (strcasecmp($this->getTrue, $rowDatum) === 0 || strcasecmp('true', $rowDatum) === 0) {
                $rowDatum = true;
            } elseif (strcasecmp($this->getFalse, $rowDatum) === 0 || strcasecmp('false', $rowDatum) === 0) {
                $rowDatum = false;
            }
        } else {
            $rowDatum = $rowDatum ?? '';
        }
    }

    
    private function convertFormattedNumber(mixed &$rowDatum): string
    {
        $numberFormatMask = '';
        if ($this->castFormattedNumberToNumeric === true && is_string($rowDatum)) {
            $numeric = str_replace(
                [$this->thousandsSeparator, $this->decimalSeparator],
                ['', '.'],
                $rowDatum
            );

            if (is_numeric($numeric)) {
                $decimalPos = strpos($rowDatum, $this->decimalSeparator);
                if ($this->preserveNumericFormatting === true) {
                    $numberFormatMask = (str_contains($rowDatum, $this->thousandsSeparator))
                        ? '
                    if ($decimalPos !== false) {
                        $decimals = strlen($rowDatum) - $decimalPos - 1;
                        $numberFormatMask .= '.' . str_repeat('0', min($decimals, 6));
                    }
                }

                $rowDatum = ($decimalPos !== false) ? (float) $numeric : (int) $numeric;
            }
        }

        return $numberFormatMask;
    }

    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }

    public function setDelimiter(?string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    public function setEnclosure(string $enclosure): self
    {
        if ($enclosure == '') {
            $enclosure = '"';
        }
        $this->enclosure = $enclosure;

        return $this;
    }

    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    public function setSheetIndex(int $indexValue): self
    {
        $this->sheetIndex = $indexValue;

        return $this;
    }

    public function setContiguous(bool $contiguous): self
    {
        $this->contiguous = $contiguous;

        return $this;
    }

    public function getContiguous(): bool
    {
        return $this->contiguous;
    }

    
    public function setEscapeCharacter(string $escapeCharacter, int $version = PHP_VERSION_ID): self
    {
        if ($version >= 90000 && $escapeCharacter !== '') {
            throw new ReaderException('Escape character must be null string for Php9+');
        }

        $this->escapeCharacter = $escapeCharacter;

        return $this;
    }

    public function getEscapeCharacter(int $version = PHP_VERSION_ID): string
    {
        return $this->escapeCharacter ?? self::getDefaultEscapeCharacter($version);
    }

    
    public function canRead(string $filename): bool
    {
        
        try {
            $this->openFile($filename);
        } catch (ReaderException) {
            return false;
        }

        fclose($this->fileHandle);

        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, ['csv', 'tsv'])) {
            return true;
        }

        
        $type = mime_content_type($filename);
        $supportedTypes = [
            'application/csv',
            'text/csv',
            'text/plain',
            'inode/x-empty',
            'text/html',
        ];

        return in_array($type, $supportedTypes, true);
    }

    private static function guessEncodingTestNoBom(string &$encoding, string &$contents, string $compare, string $setEncoding): void
    {
        if ($encoding === '') {
            $pos = strpos($contents, $compare);
            if ($pos !== false && $pos % strlen($compare) === 0) {
                $encoding = $setEncoding;
            }
        }
    }

    private static function guessEncodingNoBom(string $filename): string
    {
        $encoding = '';
        $contents = (string) file_get_contents($filename);
        self::guessEncodingTestNoBom($encoding, $contents, self::UTF32BE_LF, 'UTF-32BE');
        self::guessEncodingTestNoBom($encoding, $contents, self::UTF32LE_LF, 'UTF-32LE');
        self::guessEncodingTestNoBom($encoding, $contents, self::UTF16BE_LF, 'UTF-16BE');
        self::guessEncodingTestNoBom($encoding, $contents, self::UTF16LE_LF, 'UTF-16LE');
        if ($encoding === '' && preg_match('
            $encoding = 'UTF-8';
        }

        return $encoding;
    }

    private static function guessEncodingTestBom(string &$encoding, string $first4, string $compare, string $setEncoding): void
    {
        if ($encoding === '') {
            if (str_starts_with($first4, $compare)) {
                $encoding = $setEncoding;
            }
        }
    }

    public static function guessEncodingBom(string $filename, ?string $convertString = null): string
    {
        $encoding = '';
        $first4 = $convertString ?? (string) file_get_contents($filename, false, null, 0, 4);
        self::guessEncodingTestBom($encoding, $first4, self::UTF8_BOM, 'UTF-8');
        self::guessEncodingTestBom($encoding, $first4, self::UTF16BE_BOM, 'UTF-16BE');
        self::guessEncodingTestBom($encoding, $first4, self::UTF32BE_BOM, 'UTF-32BE');
        self::guessEncodingTestBom($encoding, $first4, self::UTF32LE_BOM, 'UTF-32LE');
        self::guessEncodingTestBom($encoding, $first4, self::UTF16LE_BOM, 'UTF-16LE');

        return $encoding;
    }

    public static function guessEncoding(string $filename, string $dflt = self::DEFAULT_FALLBACK_ENCODING): string
    {
        $encoding = self::guessEncodingBom($filename);
        if ($encoding === '') {
            $encoding = self::guessEncodingNoBom($filename);
        }

        return ($encoding === '') ? $dflt : $encoding;
    }

    public function setPreserveNullString(bool $value): self
    {
        $this->preserveNullString = $value;

        return $this;
    }

    public function getPreserveNullString(): bool
    {
        return $this->preserveNullString;
    }

    public function setSheetNameIsFileName(bool $sheetNameIsFileName): self
    {
        $this->sheetNameIsFileName = $sheetNameIsFileName;

        return $this;
    }

    
    private static function getCsv(
        $stream,
        ?int $length = null,
        string $separator = ',',
        string $enclosure = '"',
        ?string $escape = null,
        int $version = PHP_VERSION_ID
    ): array|false {
        $escape = $escape ?? self::getDefaultEscapeCharacter();
        if ($version >= 80400 && $escape !== '') {
            return @fgetcsv($stream, $length, $separator, $enclosure, $escape);
        }

        return fgetcsv($stream, $length, $separator, $enclosure, $escape);
    }

    public static function affectedByPhp9(
        string $filename,
        string $inputEncoding = 'UTF-8',
        ?string $delimiter = null,
        string $enclosure = '"',
        string $escapeCharacter = '\\',
        int $version = PHP_VERSION_ID
    ): bool {
        if ($version < 70400 || $version >= 90000) {
            throw new ReaderException('Function valid only for Php7.4 or Php8');
        }
        $reader1 = new self();
        $reader1->setInputEncoding($inputEncoding)
            ->setTestAutoDetect(true)
            ->setEscapeCharacter($escapeCharacter)
            ->setDelimiter($delimiter)
            ->setEnclosure($enclosure);
        $spreadsheet1 = $reader1->load($filename);
        $sheet1 = $spreadsheet1->getActiveSheet();
        $array1 = $sheet1->toArray(null, false, false);
        $spreadsheet1->disconnectWorksheets();

        $reader2 = new self();
        $reader2->setInputEncoding($inputEncoding)
            ->setTestAutoDetect(false)
            ->setEscapeCharacter('')
            ->setDelimiter($delimiter)
            ->setEnclosure($enclosure);
        $spreadsheet2 = $reader2->load($filename);
        $sheet2 = $spreadsheet2->getActiveSheet();
        $array2 = $sheet2->toArray(null, false, false);
        $spreadsheet2->disconnectWorksheets();

        return $array1 !== $array2;
    }

    
    private static function getDefaultEscapeCharacter(int $version = PHP_VERSION_ID): string
    {
        return $version < 90000 ? '\\' : '';
    }
}
