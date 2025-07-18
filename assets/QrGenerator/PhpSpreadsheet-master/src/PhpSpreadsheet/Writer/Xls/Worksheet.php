<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

use Composer\Pcre\Preg;
use GdImage;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Shared\Xls;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;




class Worksheet extends BIFFwriter
{
    private static int $always0 = 0;

    private static int $always1 = 1;

    
    private Parser $parser;

    
    private array $columnInfo;

    
    private int $activePane;

    
    private bool $outlineOn;

    
    private bool $outlineStyle;

    
    private bool $outlineBelow; /
    private bool $outlineRight; /
    private int $stringTotal;

    
    private int $stringUnique;

    
    private array $stringTable;

    
    private array $colors;

    
    private int $firstRowIndex;

    
    private int $lastRowIndex;

    
    private int $firstColumnIndex;

    
    private int $lastColumnIndex;

    
    public \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $phpSheet;

    
    private ?\PhpOffice\PhpSpreadsheet\Shared\Escher $escher = null;

    
    public array $fontHashIndex;

    private bool $preCalculateFormulas;

    private int $printHeaders;

    private ?Workbook $writerWorkbook;

    
    public function __construct(int &$str_total, int &$str_unique, array &$str_table, array &$colors, Parser $parser, bool $preCalculateFormulas, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $phpSheet, ?Workbook $writerWorkbook = null)
    {
        
        parent::__construct();

        $this->preCalculateFormulas = $preCalculateFormulas;
        $this->stringTotal = &$str_total;
        $this->stringUnique = &$str_unique;
        $this->stringTable = &$str_table;
        $this->colors = &$colors;
        $this->parser = $parser;

        $this->phpSheet = $phpSheet;

        $this->columnInfo = [];
        $this->activePane = 3;

        $this->printHeaders = 0;

        $this->outlineStyle = false;
        $this->outlineBelow = true;
        $this->outlineRight = true;
        $this->outlineOn = true;

        $this->fontHashIndex = [];

        
        $minR = 1;
        $minC = 'A';

        $maxR = $this->phpSheet->getHighestRow();
        $maxC = $this->phpSheet->getHighestColumn();

        
        $this->firstRowIndex = $minR;
        $this->lastRowIndex = ($maxR > 65535) ? 65535 : $maxR;

        $this->firstColumnIndex = Coordinate::columnIndexFromString($minC);
        $this->lastColumnIndex = Coordinate::columnIndexFromString($maxC);

        if ($this->lastColumnIndex > 255) {
            $this->lastColumnIndex = 255;
        }
        $this->writerWorkbook = $writerWorkbook;
    }

    
    public function close(): void
    {
        $phpSheet = $this->phpSheet;

        
        $selectedCells = $this->phpSheet->getSelectedCells();
        $activeSheetIndex = $this->phpSheet->getParentOrThrow()->getActiveSheetIndex();

        
        $this->storeBof(0x0010);

        
        $this->writePrintHeaders();

        
        $this->writePrintGridlines();

        
        $this->writeGridset();

        
        $phpSheet->calculateColumnWidths();

        
        if (($defaultWidth = $phpSheet->getDefaultColumnDimension()->getWidth()) < 0) {
            $defaultWidth = \PhpOffice\PhpSpreadsheet\Shared\Font::getDefaultColumnWidthByFont($phpSheet->getParentOrThrow()->getDefaultStyle()->getFont());
        }

        $columnDimensions = $phpSheet->getColumnDimensions();
        $maxCol = $this->lastColumnIndex - 1;
        for ($i = 0; $i <= $maxCol; ++$i) {
            $hidden = 0;
            $level = 0;
            $xfIndex = 15; 

            $width = $defaultWidth;

            $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
            if (isset($columnDimensions[$columnLetter])) {
                $columnDimension = $columnDimensions[$columnLetter];
                if ($columnDimension->getWidth() >= 0) {
                    $width = $columnDimension->getWidth();
                }
                $hidden = $columnDimension->getVisible() ? 0 : 1;
                $level = $columnDimension->getOutlineLevel();
                $xfIndex = $columnDimension->getXfIndex() + 15; 
            }

            
            
            
            
            
            
            
            $this->columnInfo[] = [$i, $i, $width, $xfIndex, $hidden, $level];
        }

        
        $this->writeGuts();

        
        $this->writeDefaultRowHeight();
        
        $this->writeWsbool();
        
        $this->writeBreaks();
        
        $this->writeHeader();
        
        $this->writeFooter();
        
        $this->writeHcenter();
        
        $this->writeVcenter();
        
        $this->writeMarginLeft();
        
        $this->writeMarginRight();
        
        $this->writeMarginTop();
        
        $this->writeMarginBottom();
        
        $this->writeSetup();
        
        $this->writeProtect();
        
        $this->writeScenProtect();
        
        $this->writeObjectProtect();
        
        $this->writePassword();
        
        $this->writeDefcol();

        
        if (!empty($this->columnInfo)) {
            $colcount = count($this->columnInfo);
            for ($i = 0; $i < $colcount; ++$i) {
                $this->writeColinfo($this->columnInfo[$i]);
            }
        }
        $autoFilterRange = $phpSheet->getAutoFilter()->getRange();
        if (!empty($autoFilterRange)) {
            
            $this->writeAutoFilterInfo();
        }

        
        $this->writeDimensions();

        
        foreach ($phpSheet->getRowDimensions() as $rowDimension) {
            $xfIndex = $rowDimension->getXfIndex() + 15; 
            $this->writeRow(
                $rowDimension->getRowIndex() - 1,
                (int) $rowDimension->getRowHeight(),
                $xfIndex,
                !$rowDimension->getVisible(),
                $rowDimension->getOutlineLevel()
            );
        }

        
        foreach ($phpSheet->getCellCollection()->getSortedCoordinates() as $coordinate) {
            
            $cell = $phpSheet->getCellCollection()->get($coordinate);
            $row = $cell->getRow() - 1;
            $column = Coordinate::columnIndexFromString($cell->getColumn()) - 1;

            
            if ($row > 65535 || $column > 255) {
                throw new WriterException('Rows or columns overflow! Excel5 has limit to 65535 rows and 255 columns. Use XLSX instead.');
            }

            
            $xfIndex = $cell->getXfIndex() + 15; 

            $cVal = $cell->getValue();
            if ($cVal instanceof RichText) {
                $arrcRun = [];
                $str_pos = 0;
                $elements = $cVal->getRichTextElements();
                foreach ($elements as $element) {
                    
                    $str_fontidx = 0;
                    if ($element instanceof Run) {
                        $getFont = $element->getFont();
                        if ($getFont !== null) {
                            $str_fontidx = $this->fontHashIndex[$getFont->getHashCode()];
                        }
                    }
                    $arrcRun[] = ['strlen' => $str_pos, 'fontidx' => $str_fontidx];
                    
                    $str_pos += StringHelper::countCharacters($element->getText(), 'UTF-8');
                }
                
                $this->writeRichTextString($row, $column, $cVal->getPlainText(), $xfIndex, $arrcRun);
            } else {
                switch ($cell->getDatatype()) {
                    case DataType::TYPE_STRING:
                    case DataType::TYPE_INLINE:
                    case DataType::TYPE_NULL:
                        if ($cVal === '' || $cVal === null) {
                            $this->writeBlank($row, $column, $xfIndex);
                        } else {
                            $this->writeString($row, $column, $cell->getValueString(), $xfIndex);
                        }

                        break;
                    case DataType::TYPE_NUMERIC:
                        $this->writeNumber($row, $column, is_numeric($cVal) ? ($cVal + 0) : 0, $xfIndex);

                        break;
                    case DataType::TYPE_FORMULA:
                        $calculatedValue = $this->preCalculateFormulas ? $cell->getCalculatedValue() : null;
                        $calculatedValueString = $this->preCalculateFormulas ? $cell->getCalculatedValueString() : '';
                        if (self::WRITE_FORMULA_EXCEPTION == $this->writeFormula($row, $column, $cell->getValueString(), $xfIndex, $calculatedValue)) {
                            if ($calculatedValue === null) {
                                $calculatedValue = $cell->getCalculatedValue();
                            }
                            $calctype = gettype($calculatedValue);
                            match ($calctype) {
                                'integer', 'double' => $this->writeNumber($row, $column, is_numeric($calculatedValue) ? ((float) $calculatedValue) : 0.0, $xfIndex),
                                'string' => $this->writeString($row, $column, $calculatedValueString, $xfIndex),
                                'boolean' => $this->writeBoolErr($row, $column, (int) $calculatedValueString, 0, $xfIndex),
                                default => $this->writeString($row, $column, $cell->getValueString(), $xfIndex),
                            };
                        }

                        break;
                    case DataType::TYPE_BOOL:
                        $this->writeBoolErr($row, $column, (int) $cell->getValueString(), 0, $xfIndex);

                        break;
                    case DataType::TYPE_ERROR:
                        $this->writeBoolErr($row, $column, ErrorCode::error($cell->getValueString()), 1, $xfIndex);

                        break;
                }
            }
        }

        
        $this->writeMsoDrawing();

        
        $this->phpSheet->getParentOrThrow()->setActiveSheetIndex($activeSheetIndex);

        
        $this->writeWindow2();

        
        $this->writePageLayoutView();

        
        $this->writeZoom();
        if ($phpSheet->getFreezePane()) {
            $this->writePanes();
        }

        
        $this->phpSheet->setSelectedCells($selectedCells);

        
        $this->writeSelection();

        
        $this->writeMergedCells();

        
        $phpParent = $phpSheet->getParent();
        $hyperlinkbase = ($phpParent === null) ? '' : $phpParent->getProperties()->getHyperlinkBase();
        foreach ($phpSheet->getHyperLinkCollection() as $coordinate => $hyperlink) {
            [$column, $row] = Coordinate::indexesFromString($coordinate);

            $url = $hyperlink->getUrl();

            if (str_contains($url, 'sheet://')) {
                
                $url = str_replace('sheet://', 'internal:', $url);
            } elseif (Preg::isMatch('/^(http:|https:|ftp:|mailto:)/', $url)) {
                
            } elseif (!empty($hyperlinkbase) && !Preg::isMatch('~^([A-Za-z]:)?[/\\\]~', $url)) {
                $url = "$hyperlinkbase$url";
                if (!Preg::isMatch('/^(http:|https:|ftp:|mailto:)/', $url)) {
                    $url = 'external:' . $url;
                }
            } else {
                
                $url = 'external:' . $url;
            }

            $this->writeUrl($row - 1, $column - 1, $url);
        }

        $this->writeDataValidity();
        $this->writeSheetLayout();

        
        $this->writeSheetProtection();
        $this->writeRangeProtection();

        
        $this->writeConditionalFormatting();

        $this->storeEof();
    }

    public const MAX_XLS_COLUMN = 256;
    public const MAX_XLS_COLUMN_STRING = 'IV';
    public const MAX_XLS_ROW = 65536;

    private static function limitRange(string $exploded): string
    {
        $retVal = '';
        $ranges = Coordinate::getRangeBoundaries($exploded);
        $firstCol = Coordinate::columnIndexFromString($ranges[0][0]);
        $firstRow = (int) $ranges[0][1];
        if ($firstCol <= self::MAX_XLS_COLUMN && $firstRow <= self::MAX_XLS_ROW) {
            $retVal = $exploded;
            if (str_contains($exploded, ':')) {
                $lastCol = Coordinate::columnIndexFromString($ranges[1][0]);
                $ranges[1][1] = min(self::MAX_XLS_ROW, (int) $ranges[1][1]);
                if ($lastCol > self::MAX_XLS_COLUMN) {
                    $ranges[1][0] = self::MAX_XLS_COLUMN_STRING;
                }
                $retVal = "{$ranges[0][0]}{$ranges[0][1]}:{$ranges[1][0]}{$ranges[1][1]}";
            }
        }

        return $retVal;
    }

    private function writeConditionalFormatting(): void
    {
        $conditionalFormulaHelper = new ConditionalHelper($this->parser);

        $arrConditionalStyles = [];
        foreach ($this->phpSheet->getConditionalStylesCollection() as $key => $value) {
            $keyExplode = explode(',', Coordinate::resolveUnionAndIntersection($key));
            foreach ($keyExplode as $exploded) {
                $range = self::limitRange($exploded);
                if ($range !== '') {
                    $arrConditionalStyles[$range] = $value;
                }
            }
        }
        if (!empty($arrConditionalStyles)) {
            
            foreach ($arrConditionalStyles as $cellCoordinate => $conditionalStyles) {
                $cfHeaderWritten = false;
                foreach ($conditionalStyles as $conditional) {
                    
                    if (
                        $conditional->getConditionType() === Conditional::CONDITION_EXPRESSION
                        || $conditional->getConditionType() === Conditional::CONDITION_CELLIS
                    ) {
                        
                        if ($cfHeaderWritten === false) {
                            $cfHeaderWritten = $this->writeCFHeader($cellCoordinate, $conditionalStyles);
                        }
                        if ($cfHeaderWritten === true) {
                            
                            $this->writeCFRule($conditionalFormulaHelper, $conditional, $cellCoordinate);
                        }
                    }
                }
            }
        }
    }

    
    private function writeBIFF8CellRangeAddressFixed(string $range): string
    {
        $explodes = explode(':', $range);

        
        $firstCell = $explodes[0];
        if (ctype_alpha($firstCell)) {
            $firstCell .= '1';
        } elseif (ctype_digit($firstCell)) {
            $firstCell = "A$firstCell";
        }

        
        if (count($explodes) == 1) {
            $lastCell = $firstCell;
        } else {
            $lastCell = $explodes[1];
        }
        if (ctype_alpha($lastCell)) {
            $lastCell .= (string) self::MAX_XLS_ROW;
        } elseif (ctype_digit($lastCell)) {
            $lastCell = self::MAX_XLS_COLUMN_STRING . $lastCell;
        }

        $firstCellCoordinates = Coordinate::indexesFromString($firstCell); 
        $lastCellCoordinates = Coordinate::indexesFromString($lastCell); 

        return pack('vvvv', $firstCellCoordinates[1] - 1, $lastCellCoordinates[1] - 1, $firstCellCoordinates[0] - 1, $lastCellCoordinates[0] - 1);
    }

    
    public function getData(): string
    {
        
        if (isset($this->_data)) {
            $tmp = $this->_data;
            $this->_data = null;

            return $tmp;
        }

        
        return '';
    }

    
    public function printRowColHeaders(int $print = 1): void
    {
        $this->printHeaders = $print;
    }

    
    public function setOutline(bool $visible = true, bool $symbols_below = true, bool $symbols_right = true, bool $auto_style = false): void
    {
        $this->outlineOn = $visible;
        $this->outlineBelow = $symbols_below;
        $this->outlineRight = $symbols_right;
        $this->outlineStyle = $auto_style;
    }

    
    private function writeNumber(int $row, int $col, float $num, int $xfIndex): int
    {
        $record = 0x0203; 
        $length = 0x000E; 

        $header = pack('vv', $record, $length);
        $data = pack('vvv', $row, $col, $xfIndex);
        $xl_double = pack('d', $num);
        if (self::getByteOrder()) { 
            $xl_double = strrev($xl_double);
        }

        $this->append($header . $data . $xl_double);

        return 0;
    }

    
    private function writeString(int $row, int $col, string $str, int $xfIndex): void
    {
        $this->writeLabelSst($row, $col, $str, $xfIndex);
    }

    
    private function writeRichTextString(int $row, int $col, string $str, int $xfIndex, array $arrcRun): void
    {
        $record = 0x00FD; 
        $length = 0x000A; 
        $str = StringHelper::UTF8toBIFF8UnicodeShort($str, $arrcRun);

        
        if (!isset($this->stringTable[$str])) {
            $this->stringTable[$str] = $this->stringUnique++;
        }
        ++$this->stringTotal;

        $header = pack('vv', $record, $length);
        $data = pack('vvvV', $row, $col, $xfIndex, $this->stringTable[$str]);
        $this->append($header . $data);
    }

    
    private function writeLabelSst(int $row, int $col, string $str, int $xfIndex): void
    {
        $record = 0x00FD; 
        $length = 0x000A; 

        $str = StringHelper::UTF8toBIFF8UnicodeLong($str);

        
        if (!isset($this->stringTable[$str])) {
            $this->stringTable[$str] = $this->stringUnique++;
        }
        ++$this->stringTotal;

        $header = pack('vv', $record, $length);
        $data = pack('vvvV', $row, $col, $xfIndex, $this->stringTable[$str]);
        $this->append($header . $data);
    }

    
    public function writeBlank(int $row, int $col, int $xfIndex): int
    {
        $record = 0x0201; 
        $length = 0x0006; 

        $header = pack('vv', $record, $length);
        $data = pack('vvv', $row, $col, $xfIndex);
        $this->append($header . $data);

        return 0;
    }

    
    private function writeBoolErr(int $row, int $col, int $value, int $isError, int $xfIndex): int
    {
        $record = 0x0205;
        $length = 8;

        $header = pack('vv', $record, $length);
        $data = pack('vvvCC', $row, $col, $xfIndex, $value, $isError);
        $this->append($header . $data);

        return 0;
    }

    const WRITE_FORMULA_NORMAL = 0;
    const WRITE_FORMULA_ERRORS = -1;
    const WRITE_FORMULA_RANGE = -2;
    const WRITE_FORMULA_EXCEPTION = -3;

    private static bool $allowThrow = false;

    public static function setAllowThrow(bool $allowThrow): void
    {
        self::$allowThrow = $allowThrow;
    }

    public static function getAllowThrow(): bool
    {
        return self::$allowThrow;
    }

    
    private function writeFormula(int $row, int $col, string $formula, int $xfIndex, mixed $calculatedValue): int
    {
        $record = 0x0006; 
        
        $stringValue = null;

        
        if (isset($calculatedValue)) {
            
            
            if (is_bool($calculatedValue)) {
                
                $num = pack('CCCvCv', 0x01, 0x00, (int) $calculatedValue, 0x00, 0x00, 0xFFFF);
            } elseif (is_int($calculatedValue) || is_float($calculatedValue)) {
                
                $num = pack('d', $calculatedValue);
            } elseif (is_string($calculatedValue)) {
                $errorCodes = DataType::getErrorCodes();
                if (isset($errorCodes[$calculatedValue])) {
                    
                    $num = pack('CCCvCv', 0x02, 0x00, ErrorCode::error($calculatedValue), 0x00, 0x00, 0xFFFF);
                } elseif ($calculatedValue === '') {
                    
                    $num = pack('CCCvCv', 0x03, 0x00, 0x00, 0x00, 0x00, 0xFFFF);
                } else {
                    
                    $stringValue = $calculatedValue;
                    $num = pack('CCCvCv', 0x00, 0x00, 0x00, 0x00, 0x00, 0xFFFF);
                }
            } else {
                
                $num = pack('d', 0x00);
            }
        } else {
            $num = pack('d', 0x00);
        }

        $grbit = 0x03; 
        $unknown = 0x0000; 

        
        if ($formula[0] == '=') {
            $formula = substr($formula, 1);
        } else {
            
            $this->writeString($row, $col, 'Unrecognised character for formula', 0);

            return self::WRITE_FORMULA_ERRORS;
        }

        
        try {
            $this->parser->parse($formula);
            $formula = $this->parser->toReversePolish();

            $formlen = strlen($formula); 
            $length = 0x16 + $formlen; 

            $header = pack('vv', $record, $length);

            $data = pack('vvv', $row, $col, $xfIndex)
                . $num
                . pack('vVv', $grbit, $unknown, $formlen);
            $this->append($header . $data . $formula);

            
            if ($stringValue !== null) {
                $this->writeStringRecord($stringValue);
            }

            return self::WRITE_FORMULA_NORMAL;
        } catch (PhpSpreadsheetException $e) {
            if (self::$allowThrow) {
                throw $e;
            }

            return self::WRITE_FORMULA_EXCEPTION;
        }
    }

    
    private function writeStringRecord(string $stringValue): void
    {
        $record = 0x0207; 
        $data = StringHelper::UTF8toBIFF8UnicodeLong($stringValue);

        $length = strlen($data);
        $header = pack('vv', $record, $length);

        $this->append($header . $data);
    }

    
    private function writeUrl(int $row, int $col, string $url): void
    {
        
        $this->writeUrlRange($row, $col, $row, $col, $url);
    }

    
    private function writeUrlRange(int $row1, int $col1, int $row2, int $col2, string $url): void
    {
        
        if (Preg::isMatch('[^internal:]', $url)) {
            $this->writeUrlInternal($row1, $col1, $row2, $col2, $url);
        }
        if (Preg::isMatch('[^external:]', $url)) {
            $this->writeUrlExternal($row1, $col1, $row2, $col2, $url);
        }

        $this->writeUrlWeb($row1, $col1, $row2, $col2, $url);
    }

    
    public function writeUrlWeb(int $row1, int $col1, int $row2, int $col2, string $url): void
    {
        $record = 0x01B8; 

        
        $unknown1 = pack('H*', 'D0C9EA79F9BACE118C8200AA004BA90B02000000');
        $unknown2 = pack('H*', 'E0C9EA79F9BACE118C8200AA004BA90B');

        
        $options = pack('V', 0x03);

        

        $url = implode("\0", Preg::split("''", $url, -1, PREG_SPLIT_NO_EMPTY));
        $url = $url . "\0\0\0";

        
        $url_len = pack('V', strlen($url));

        
        $length = 0x34 + strlen($url);

        
        $header = pack('vv', $record, $length);
        $data = pack('vvvv', $row1, $row2, $col1, $col2);

        
        $this->append($header . $data . $unknown1 . $options . $unknown2 . $url_len . $url);
    }

    
    private function writeUrlInternal(int $row1, int $col1, int $row2, int $col2, string $url): void
    {
        $record = 0x01B8; 

        
        $url = Preg::replace('/^internal:/', '', $url);

        
        $unknown1 = pack('H*', 'D0C9EA79F9BACE118C8200AA004BA90B02000000');

        
        $options = pack('V', 0x08);

        
        $url .= "\0";

        
        $url_len = StringHelper::countCharacters($url);
        $url_len = pack('V', $url_len);

        $url = StringHelper::convertEncoding($url, 'UTF-16LE', 'UTF-8');

        
        $length = 0x24 + strlen($url);

        
        $header = pack('vv', $record, $length);
        $data = pack('vvvv', $row1, $row2, $col1, $col2);

        
        $this->append($header . $data . $unknown1 . $options . $url_len . $url);
    }

    
    private function writeUrlExternal(int $row1, int $col1, int $row2, int $col2, string $url): void
    {
        
        
        if (Preg::isMatch('[^external:\\\]', $url)) {
            return;
        }

        $record = 0x01B8; 

        
        
        $url = Preg::replace(['/^external:/', '/\

        
        
        
        

        $absolute = 0x00; 
        if (Preg::isMatch('/^[A-Z]:/', $url)) {
            $absolute = 0x02; 
        }
        $link_type = 0x01 | $absolute;

        
        
        
        $dir_long = $url;
        if (Preg::isMatch('/\
            $link_type |= 0x08;
        }

        
        $link_type = pack('V', $link_type);

        
        $up_count = Preg::isMatchAll('/\.\.\\\/', $dir_long, $useless);
        $up_count = pack('v', $up_count);

        
        $dir_short = Preg::replace('/\.\.\\\/', '', $dir_long) . "\0";

        
        

        
        $dir_short_len = pack('V', strlen($dir_short));
        
        $stream_len = pack('V', 0); 

        
        $unknown1 = pack('H*', 'D0C9EA79F9BACE118C8200AA004BA90B02000000');
        $unknown2 = pack('H*', '0303000000000000C000000000000046');
        $unknown3 = pack('H*', 'FFFFADDE000000000000000000000000000000000000000');
        

        
        $data = pack('vvvv', $row1, $row2, $col1, $col2)
            . $unknown1
            . $link_type
            . $unknown2
            . $up_count
            . $dir_short_len
            . $dir_short
            . $unknown3
            . $stream_len; 

        
        $length = strlen($data);
        $header = pack('vv', $record, $length);

        
        $this->append($header . $data);
    }

    
    private function writeRow(int $row, int $height, int $xfIndex, bool $hidden = false, int $level = 0): void
    {
        $record = 0x0208; 
        $length = 0x0010; 

        $colMic = 0x0000; 
        $colMac = 0x0000; 
        $irwMac = 0x0000; 
        $reserved = 0x0000; 
        $grbit = 0x0000; 
        $ixfe = $xfIndex;

        if ($height < 0) {
            $height = null;
        }

        
        if ($height !== null) {
            $miyRw = $height * 20; 
        } else {
            $miyRw = 0xFF; 
        }

        
        
        
        
        

        $grbit |= $level;
        if ($hidden === true) {
            $grbit |= 0x0030;
        }
        if ($height !== null) {
            $grbit |= 0x0040; 
        }
        if ($xfIndex !== 0xF) {
            $grbit |= 0x0080;
        }
        $grbit |= 0x0100;

        $header = pack('vv', $record, $length);
        $data = pack('vvvvvvvv', $row, $colMic, $colMac, $miyRw, $irwMac, $reserved, $grbit, $ixfe);
        $this->append($header . $data);
    }

    
    private function writeDimensions(): void
    {
        $record = 0x0200; 

        $length = 0x000E;
        $data = pack('VVvvv', $this->firstRowIndex, $this->lastRowIndex + 1, $this->firstColumnIndex, $this->lastColumnIndex + 1, 0x0000); 

        $header = pack('vv', $record, $length);
        $this->append($header . $data);
    }

    
    private function writeWindow2(): void
    {
        $record = 0x023E; 
        $length = 0x0012;

        $rwTop = 0x0000; 
        $colLeft = 0x0000; 

        
        $fDspFmla = 0; 
        $fDspGrid = $this->phpSheet->getShowGridlines() ? 1 : 0; 
        $fDspRwCol = $this->phpSheet->getShowRowColHeaders() ? 1 : 0; 
        $fFrozen = $this->phpSheet->getFreezePane() ? 1 : 0; 
        $fDspZeros = 1; 
        $fDefaultHdr = 1; 
        $fArabic = $this->phpSheet->getRightToLeft() ? 1 : 0; 
        $fDspGuts = $this->outlineOn; 
        $fFrozenNoSplit = 0; 
        
        $fSelected = ($this->phpSheet === $this->phpSheet->getParentOrThrow()->getActiveSheet()) ? 1 : 0;
        $fPageBreakPreview = $this->phpSheet->getSheetView()->getView() === SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW;

        $grbit = $fDspFmla;
        $grbit |= $fDspGrid << 1;
        $grbit |= $fDspRwCol << 2;
        $grbit |= $fFrozen << 3;
        $grbit |= $fDspZeros << 4;
        $grbit |= $fDefaultHdr << 5;
        $grbit |= $fArabic << 6;
        $grbit |= $fDspGuts << 7;
        $grbit |= $fFrozenNoSplit << 8;
        $grbit |= $fSelected << 9; 
        $grbit |= $fSelected << 10; 
        $grbit |= $fPageBreakPreview << 11;

        $header = pack('vv', $record, $length);
        $data = pack('vvv', $grbit, $rwTop, $colLeft);

        
        $rgbHdr = 0x0040; 
        $zoom_factor_page_break = ($fPageBreakPreview ? $this->phpSheet->getSheetView()->getZoomScale() : 0x0000);
        $zoom_factor_normal = $this->phpSheet->getSheetView()->getZoomScaleNormal();

        $data .= pack('vvvvV', $rgbHdr, 0x0000, $zoom_factor_page_break, $zoom_factor_normal, 0x00000000);

        $this->append($header . $data);
    }

    
    private function writeDefaultRowHeight(): void
    {
        $defaultRowHeight = $this->phpSheet->getDefaultRowDimension()->getRowHeight();

        if ($defaultRowHeight < 0) {
            return;
        }

        
        $defaultRowHeight = (int) 20 * $defaultRowHeight;

        $record = 0x0225; 
        $length = 0x0004; 

        $header = pack('vv', $record, $length);
        $data = pack('vv', 1, $defaultRowHeight);
        $this->append($header . $data);
    }

    
    private function writeDefcol(): void
    {
        $defaultColWidth = 8;

        $record = 0x0055; 
        $length = 0x0002; 

        $header = pack('vv', $record, $length);
        $data = pack('v', $defaultColWidth);
        $this->append($header . $data);
    }

    
    private function writeColinfo(array $col_array): void
    {
        $colFirst = $col_array[0] ?? null;
        $colLast = $col_array[1] ?? null;
        $coldx = $col_array[2] ?? 8.43;
        $xfIndex = $col_array[3] ?? 15;
        $grbit = $col_array[4] ?? 0;
        $level = $col_array[5] ?? 0;

        $record = 0x007D; 
        $length = 0x000C; 

        $coldx *= 256; 

        $ixfe = $xfIndex;
        $reserved = 0x0000; 

        $level = max(0, min($level, 7));
        $grbit |= $level << 8;

        $header = pack('vv', $record, $length);
        $data = pack('vvvvvv', $colFirst, $colLast, $coldx, $ixfe, $grbit, $reserved);
        $this->append($header . $data);
    }

    
    private function writeSelection(): void
    {
        
        $selectedCells = Coordinate::splitRange($this->phpSheet->getSelectedCells());
        $selectedCells = $selectedCells[0];
        if (count($selectedCells) == 2) {
            [$first, $last] = $selectedCells;
        } else {
            $first = $selectedCells[0];
            $last = $selectedCells[0];
        }

        [$colFirst, $rwFirst] = Coordinate::coordinateFromString($first);
        $colFirst = Coordinate::columnIndexFromString($colFirst) - 1; 
        --$rwFirst; 

        [$colLast, $rwLast] = Coordinate::coordinateFromString($last);
        $colLast = Coordinate::columnIndexFromString($colLast) - 1; 
        --$rwLast; 

        
        $colFirst = min($colFirst, 255);
        $colLast = min($colLast, 255);

        $rwFirst = min($rwFirst, 65535);
        $rwLast = min($rwLast, 65535);

        $record = 0x001D; 
        $length = 0x000F; 

        $pnn = $this->activePane; 
        $rwAct = $rwFirst; 
        $colAct = $colFirst; 
        $irefAct = 0; 
        $cref = 1; 

        
        if ($rwFirst > $rwLast) {
            [$rwFirst, $rwLast] = [$rwLast, $rwFirst];
        }

        if ($colFirst > $colLast) {
            [$colFirst, $colLast] = [$colLast, $colFirst];
        }

        $header = pack('vv', $record, $length);
        $data = pack('CvvvvvvCC', $pnn, $rwAct, $colAct, $irefAct, $cref, $rwFirst, $rwLast, $colFirst, $colLast);
        $this->append($header . $data);
    }

    
    private function writeMergedCells(): void
    {
        $mergeCells = $this->phpSheet->getMergeCells();
        $countMergeCells = count($mergeCells);

        if ($countMergeCells == 0) {
            return;
        }

        
        $maxCountMergeCellsPerRecord = 1027;

        
        $record = 0x00E5;

        
        $i = 0;

        
        $j = 0;

        
        $recordData = '';

        
        foreach ($mergeCells as $mergeCell) {
            ++$i;
            ++$j;

            
            $range = Coordinate::splitRange($mergeCell);
            [$first, $last] = $range[0];
            [$firstColumn, $firstRow] = Coordinate::indexesFromString($first);
            [$lastColumn, $lastRow] = Coordinate::indexesFromString($last);

            $recordData .= pack('vvvv', $firstRow - 1, $lastRow - 1, $firstColumn - 1, $lastColumn - 1);

            
            if ($j == $maxCountMergeCellsPerRecord || $i == $countMergeCells) {
                $recordData = pack('v', $j) . $recordData;
                $length = strlen($recordData);
                $header = pack('vv', $record, $length);
                $this->append($header . $recordData);

                
                $recordData = '';
                $j = 0;
            }
        }
    }

    
    private function writeSheetLayout(): void
    {
        if (!$this->phpSheet->isTabColorSet()) {
            return;
        }

        $recordData = pack(
            'vvVVVvv',
            0x0862,
            0x0000, 
            0x00000000, 
            0x00000000, 
            0x00000014, 
            $this->colors[$this->phpSheet->getTabColor()->getRGB()], 
            0x0000        
        );

        $length = strlen($recordData);

        $record = 0x0862; 
        $header = pack('vv', $record, $length);
        $this->append($header . $recordData);
    }

    private static function protectionBitsDefaultFalse(?bool $value, int $shift): int
    {
        if ($value === false) {
            return 1 << $shift;
        }

        return 0;
    }

    private static function protectionBitsDefaultTrue(?bool $value, int $shift): int
    {
        if ($value !== false) {
            return 1 << $shift;
        }

        return 0;
    }

    
    private function writeSheetProtection(): void
    {
        
        $record = 0x0867;

        
        $protection = $this->phpSheet->getProtection();
        $options = self::protectionBitsDefaultTrue($protection->getObjects(), 0)
            | self::protectionBitsDefaultTrue($protection->getScenarios(), 1)
            | self::protectionBitsDefaultFalse($protection->getFormatCells(), 2)
            | self::protectionBitsDefaultFalse($protection->getFormatColumns(), 3)
            | self::protectionBitsDefaultFalse($protection->getFormatRows(), 4)
            | self::protectionBitsDefaultFalse($protection->getInsertColumns(), 5)
            | self::protectionBitsDefaultFalse($protection->getInsertRows(), 6)
            | self::protectionBitsDefaultFalse($protection->getInsertHyperlinks(), 7)
            | self::protectionBitsDefaultFalse($protection->getDeleteColumns(), 8)
            | self::protectionBitsDefaultFalse($protection->getDeleteRows(), 9)
            | self::protectionBitsDefaultTrue($protection->getSelectLockedCells(), 10)
            | self::protectionBitsDefaultFalse($protection->getSort(), 11)
            | self::protectionBitsDefaultFalse($protection->getAutoFilter(), 12)
            | self::protectionBitsDefaultFalse($protection->getPivotTables(), 13)
            | self::protectionBitsDefaultTrue($protection->getSelectUnlockedCells(), 14);

        
        $recordData = pack(
            'vVVCVVvv',
            0x0867, 
            0x0000, 
            0x0000, 
            0x00, 
            0x01000200, 
            0xFFFFFFFF, 
            $options, 
            0x0000 
        );

        $length = strlen($recordData);
        $header = pack('vv', $record, $length);

        $this->append($header . $recordData);
    }

    
    private function writeRangeProtection(): void
    {
        foreach ($this->phpSheet->getProtectedCellRanges() as $range => $protectedCells) {
            $password = $protectedCells->getPassword();
            
            $cellRanges = explode(' ', $range);
            $cref = count($cellRanges);

            $recordData = pack(
                'vvVVvCVvVv',
                0x0868,
                0x00,
                0x0000,
                0x0000,
                0x02,
                0x0,
                0x0000,
                $cref,
                0x0000,
                0x00
            );

            foreach ($cellRanges as $cellRange) {
                $recordData .= $this->writeBIFF8CellRangeAddressFixed($cellRange);
            }

            
            $recordData .= pack(
                'VV',
                0x0000,
                hexdec($password)
            );

            $recordData .= StringHelper::UTF8toBIFF8UnicodeLong('p' . md5($recordData));

            $length = strlen($recordData);

            $record = 0x0868; 
            $header = pack('vv', $record, $length);
            $this->append($header . $recordData);
        }
    }

    
    private function writePanes(): void
    {
        if (!$this->phpSheet->getFreezePane()) {
            
            return;
        }

        [$column, $row] = Coordinate::indexesFromString($this->phpSheet->getFreezePane());
        $x = $column - 1;
        $y = $row - 1;

        [$leftMostColumn, $topRow] = Coordinate::indexesFromString($this->phpSheet->getTopLeftCell() ?? '');
        
        $rwTop = $topRow - 1;
        $colLeft = $leftMostColumn - 1;

        $record = 0x0041; 
        $length = 0x000A; 

        
        
        $pnnAct = 0;
        if ($x != 0 && $y != 0) {
            $pnnAct = 0; 
        }
        if ($x != 0 && $y == 0) {
            $pnnAct = 1; 
        }
        if ($x == 0 && $y != 0) {
            $pnnAct = 2; 
        }
        if ($x == 0 && $y == 0) {
            $pnnAct = 3; 
        }

        $this->activePane = $pnnAct; 

        $header = pack('vv', $record, $length);
        $data = pack('vvvvv', $x, $y, $rwTop, $colLeft, $pnnAct);
        $this->append($header . $data);
    }

    
    private function writeSetup(): void
    {
        $record = 0x00A1; 
        $length = 0x0022; 

        $iPaperSize = $this->phpSheet->getPageSetup()->getPaperSize(); 
        $iScale = $this->phpSheet->getPageSetup()->getScale() ?: 100; 

        $iPageStart = 0x01; 
        $iFitWidth = (int) $this->phpSheet->getPageSetup()->getFitToWidth(); 
        $iFitHeight = (int) $this->phpSheet->getPageSetup()->getFitToHeight(); 
        $iRes = 0x0258; 
        $iVRes = 0x0258; 

        $numHdr = $this->phpSheet->getPageMargins()->getHeader(); 

        $numFtr = $this->phpSheet->getPageMargins()->getFooter(); 
        $iCopies = 0x01; 

        
        $fLeftToRight = $this->phpSheet->getPageSetup()->getPageOrder() === PageSetup::PAGEORDER_DOWN_THEN_OVER
            ? 0x0 : 0x1;
        
        $fLandscape = ($this->phpSheet->getPageSetup()->getOrientation() == PageSetup::ORIENTATION_LANDSCAPE)
            ? 0x0 : 0x1;

        $fNoPls = 0x0; 
        $fNoColor = 0x0; 
        $fDraft = 0x0; 
        $fNotes = 0x0; 
        $fNoOrient = 0x0; 
        $fUsePage = 0x0; 

        $grbit = $fLeftToRight;
        $grbit |= $fLandscape << 1;
        $grbit |= $fNoPls << 2;
        $grbit |= $fNoColor << 3;
        $grbit |= $fDraft << 4;
        $grbit |= $fNotes << 5;
        $grbit |= $fNoOrient << 6;
        $grbit |= $fUsePage << 7;

        $numHdr = pack('d', $numHdr);
        $numFtr = pack('d', $numFtr);
        if (self::getByteOrder()) { 
            $numHdr = strrev($numHdr);
            $numFtr = strrev($numFtr);
        }

        $header = pack('vv', $record, $length);
        $data1 = pack('vvvvvvvv', $iPaperSize, $iScale, $iPageStart, $iFitWidth, $iFitHeight, $grbit, $iRes, $iVRes);
        $data2 = $numHdr . $numFtr;
        $data3 = pack('v', $iCopies);
        $this->append($header . $data1 . $data2 . $data3);
    }

    
    private function writeHeader(): void
    {
        $record = 0x0014; 

        

        $recordData = StringHelper::UTF8toBIFF8UnicodeLong($this->phpSheet->getHeaderFooter()->getOddHeader());
        $length = strlen($recordData);

        $header = pack('vv', $record, $length);

        $this->append($header . $recordData);
    }

    
    private function writeFooter(): void
    {
        $record = 0x0015; 

        

        $recordData = StringHelper::UTF8toBIFF8UnicodeLong($this->phpSheet->getHeaderFooter()->getOddFooter());
        $length = strlen($recordData);

        $header = pack('vv', $record, $length);

        $this->append($header . $recordData);
    }

    
    private function writeHcenter(): void
    {
        $record = 0x0083; 
        $length = 0x0002; 

        $fHCenter = $this->phpSheet->getPageSetup()->getHorizontalCentered() ? 1 : 0; 

        $header = pack('vv', $record, $length);
        $data = pack('v', $fHCenter);

        $this->append($header . $data);
    }

    
    private function writeVcenter(): void
    {
        $record = 0x0084; 
        $length = 0x0002; 

        $fVCenter = $this->phpSheet->getPageSetup()->getVerticalCentered() ? 1 : 0; 

        $header = pack('vv', $record, $length);
        $data = pack('v', $fVCenter);
        $this->append($header . $data);
    }

    
    private function writeMarginLeft(): void
    {
        $record = 0x0026; 
        $length = 0x0008; 

        $margin = $this->phpSheet->getPageMargins()->getLeft(); 

        $header = pack('vv', $record, $length);
        $data = pack('d', $margin);
        if (self::getByteOrder()) { 
            $data = strrev($data);
        }

        $this->append($header . $data);
    }

    
    private function writeMarginRight(): void
    {
        $record = 0x0027; 
        $length = 0x0008; 

        $margin = $this->phpSheet->getPageMargins()->getRight(); 

        $header = pack('vv', $record, $length);
        $data = pack('d', $margin);
        if (self::getByteOrder()) { 
            $data = strrev($data);
        }

        $this->append($header . $data);
    }

    
    private function writeMarginTop(): void
    {
        $record = 0x0028; 
        $length = 0x0008; 

        $margin = $this->phpSheet->getPageMargins()->getTop(); 

        $header = pack('vv', $record, $length);
        $data = pack('d', $margin);
        if (self::getByteOrder()) { 
            $data = strrev($data);
        }

        $this->append($header . $data);
    }

    
    private function writeMarginBottom(): void
    {
        $record = 0x0029; 
        $length = 0x0008; 

        $margin = $this->phpSheet->getPageMargins()->getBottom(); 

        $header = pack('vv', $record, $length);
        $data = pack('d', $margin);
        if (self::getByteOrder()) { 
            $data = strrev($data);
        }

        $this->append($header . $data);
    }

    
    private function writePrintHeaders(): void
    {
        $record = 0x002A; 
        $length = 0x0002; 

        $fPrintRwCol = $this->printHeaders; 

        $header = pack('vv', $record, $length);
        $data = pack('v', $fPrintRwCol);
        $this->append($header . $data);
    }

    
    private function writePrintGridlines(): void
    {
        $record = 0x002B; 
        $length = 0x0002; 

        $fPrintGrid = $this->phpSheet->getPrintGridlines() ? 1 : 0; 

        $header = pack('vv', $record, $length);
        $data = pack('v', $fPrintGrid);
        $this->append($header . $data);
    }

    
    private function writeGridset(): void
    {
        $record = 0x0082; 
        $length = 0x0002; 

        $fGridSet = !$this->phpSheet->getPrintGridlines(); 

        $header = pack('vv', $record, $length);
        $data = pack('v', $fGridSet);
        $this->append($header . $data);
    }

    
    private function writeAutoFilterInfo(): void
    {
        $record = 0x009D; 
        $length = 0x0002; 

        $rangeBounds = Coordinate::rangeBoundaries($this->phpSheet->getAutoFilter()->getRange());
        $iNumFilters = 1 + $rangeBounds[1][0] - $rangeBounds[0][0];

        $header = pack('vv', $record, $length);
        $data = pack('v', $iNumFilters);
        $this->append($header . $data);
    }

    
    private function writeGuts(): void
    {
        $record = 0x0080; 
        $length = 0x0008; 

        $dxRwGut = 0x0000; 
        $dxColGut = 0x0000; 

        
        $maxRowOutlineLevel = 0;
        foreach ($this->phpSheet->getRowDimensions() as $rowDimension) {
            $maxRowOutlineLevel = max($maxRowOutlineLevel, $rowDimension->getOutlineLevel());
        }

        $col_level = 0;

        
        
        $colcount = count($this->columnInfo);
        for ($i = 0; $i < $colcount; ++$i) {
            $col_level = max($this->columnInfo[$i][5], $col_level);
        }

        
        $col_level = max(0, min($col_level, 7));

        
        if ($maxRowOutlineLevel) {
            ++$maxRowOutlineLevel;
        }
        if ($col_level) {
            ++$col_level;
        }

        $header = pack('vv', $record, $length);
        $data = pack('vvvv', $dxRwGut, $dxColGut, $maxRowOutlineLevel, $col_level);

        $this->append($header . $data);
    }

    
    private function writeWsbool(): void
    {
        $record = 0x0081; 
        $length = 0x0002; 
        $grbit = 0x0000;

        
        
        
        
        $grbit |= 0x0001; 
        if ($this->outlineStyle) {
            $grbit |= 0x0020; 
        }
        if ($this->phpSheet->getShowSummaryBelow()) {
            $grbit |= 0x0040; 
        }
        if ($this->phpSheet->getShowSummaryRight()) {
            $grbit |= 0x0080; 
        }
        if ($this->phpSheet->getPageSetup()->getFitToPage()) {
            $grbit |= 0x0100; 
        }
        if ($this->outlineOn) {
            $grbit |= 0x0400; 
        }

        $header = pack('vv', $record, $length);
        $data = pack('v', $grbit);
        $this->append($header . $data);
    }

    
    private function writeBreaks(): void
    {
        
        $vbreaks = [];
        $hbreaks = [];

        foreach ($this->phpSheet->getRowBreaks() as $cell => $break) {
            
            $coordinates = Coordinate::coordinateFromString($cell);
            $hbreaks[] = $coordinates[1];
        }
        foreach ($this->phpSheet->getColumnBreaks() as $cell => $break) {
            
            $coordinates = Coordinate::indexesFromString($cell);
            $vbreaks[] = $coordinates[0] - 1;
        }

        
        if (!empty($hbreaks)) {
            
            sort($hbreaks, SORT_NUMERIC);
            if ($hbreaks[0] == 0) { 
                array_shift($hbreaks);
            }

            $record = 0x001B; 
            $cbrk = count($hbreaks); 
            $length = 2 + 6 * $cbrk; 

            $header = pack('vv', $record, $length);
            $data = pack('v', $cbrk);

            
            foreach ($hbreaks as $hbreak) {
                $data .= pack('vvv', $hbreak, 0x0000, 0x00FF);
            }

            $this->append($header . $data);
        }

        
        if (!empty($vbreaks)) {
            
            
            $vbreaks = array_slice($vbreaks, 0, 1000);

            
            sort($vbreaks, SORT_NUMERIC);
            if ($vbreaks[0] == 0) { 
                array_shift($vbreaks);
            }

            $record = 0x001A; 
            $cbrk = count($vbreaks); 
            $length = 2 + 6 * $cbrk; 

            $header = pack('vv', $record, $length);
            $data = pack('v', $cbrk);

            
            foreach ($vbreaks as $vbreak) {
                $data .= pack('vvv', $vbreak, 0x0000, 0xFFFF);
            }

            $this->append($header . $data);
        }
    }

    
    private function writeProtect(): void
    {
        
        if ($this->phpSheet->getProtection()->getSheet() !== true) {
            return;
        }

        $record = 0x0012; 
        $length = 0x0002; 

        $fLock = 1; 

        $header = pack('vv', $record, $length);
        $data = pack('v', $fLock);

        $this->append($header . $data);
    }

    
    private function writeScenProtect(): void
    {
        
        if ($this->phpSheet->getProtection()->getSheet() !== true) {
            return;
        }

        
        if ($this->phpSheet->getProtection()->getScenarios() !== true) {
            return;
        }

        $record = 0x00DD; 
        $length = 0x0002; 

        $header = pack('vv', $record, $length);
        $data = pack('v', 1);

        $this->append($header . $data);
    }

    
    private function writeObjectProtect(): void
    {
        
        if ($this->phpSheet->getProtection()->getSheet() !== true) {
            return;
        }

        
        if ($this->phpSheet->getProtection()->getObjects() !== true) {
            return;
        }

        $record = 0x0063; 
        $length = 0x0002; 

        $header = pack('vv', $record, $length);
        $data = pack('v', 1);

        $this->append($header . $data);
    }

    
    private function writePassword(): void
    {
        
        if ($this->phpSheet->getProtection()->getSheet() !== true || !$this->phpSheet->getProtection()->getPassword() || $this->phpSheet->getProtection()->getAlgorithm() !== '') {
            return;
        }

        $record = 0x0013; 
        $length = 0x0002; 

        $wPassword = hexdec($this->phpSheet->getProtection()->getPassword()); 

        $header = pack('vv', $record, $length);
        $data = pack('v', $wPassword);

        $this->append($header . $data);
    }

    
    public function insertBitmap(int $row, int $col, GdImage|string $bitmap, int $x = 0, int $y = 0, float $scale_x = 1, float $scale_y = 1): void
    {
        $bitmap_array = $bitmap instanceof GdImage
            ? $this->processBitmapGd($bitmap)
            : $this->processBitmap($bitmap);
        [$width, $height, $size, $data] = $bitmap_array;
        
        
        
        

        
        $width *= $scale_x;
        $height *= $scale_y;

        
        $this->positionImage($col, $row, $x, $y, (int) $width, (int) $height);

        
        $record = 0x007F;
        $length = 8 + $size;
        $cf = 0x09;
        $env = 0x01;
        $lcb = $size;

        $header = pack('vvvvV', $record, $length, $cf, $env, $lcb);
        $this->append($header . $data);
    }

    
    public function positionImage(int $col_start, int $row_start, int $x1, int $y1, int $width, int $height): void
    {
        
        $col_end = $col_start; 
        $row_end = $row_start; 

        
        if ($x1 >= Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_start + 1))) {
            $x1 = 0;
        }
        if ($y1 >= Xls::sizeRow($this->phpSheet, $row_start + 1)) {
            $y1 = 0;
        }

        $width = $width + $x1 - 1;
        $height = $height + $y1 - 1;

        
        while ($width >= Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_end + 1))) {
            $width -= Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_end + 1));
            ++$col_end;
        }

        
        while ($height >= Xls::sizeRow($this->phpSheet, $row_end + 1)) {
            $height -= Xls::sizeRow($this->phpSheet, $row_end + 1);
            ++$row_end;
        }

        
        
        
        if (Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_start + 1)) == 0) {
            return;
        }
        if (Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_end + 1)) == 0) {
            return;
        }
        if (Xls::sizeRow($this->phpSheet, $row_start + 1) == 0) {
            return;
        }
        if (Xls::sizeRow($this->phpSheet, $row_end + 1) == 0) {
            return;
        }

        
        $x1 = $x1 / Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_start + 1)) * 1024;
        $y1 = $y1 / Xls::sizeRow($this->phpSheet, $row_start + 1) * 256;
        $x2 = $width / Xls::sizeCol($this->phpSheet, Coordinate::stringFromColumnIndex($col_end + 1)) * 1024; 
        $y2 = $height / Xls::sizeRow($this->phpSheet, $row_end + 1) * 256; 

        $this->writeObjPicture($col_start, $x1, $row_start, $y1, $col_end, $x2, $row_end, $y2);
    }

    
    private function writeObjPicture(int $colL, int $dxL, int $rwT, int|float $dyT, int $colR, int $dxR, int $rwB, int $dyB): void
    {
        $record = 0x005D; 
        $length = 0x003C; 

        $cObj = 0x0001; 
        $OT = 0x0008; 
        $id = 0x0001; 
        $grbit = 0x0614; 

        $cbMacro = 0x0000; 
        $Reserved1 = 0x0000; 
        $Reserved2 = 0x0000; 

        $icvBack = 0x09; 
        $icvFore = 0x09; 
        $fls = 0x00; 
        $fAuto = 0x00; 
        $icv = 0x08; 
        $lns = 0xFF; 
        $lnw = 0x01; 
        $fAutoB = 0x00; 
        $frs = 0x0000; 
        $cf = 0x0009; 
        $Reserved3 = 0x0000; 
        $cbPictFmla = 0x0000; 
        $Reserved4 = 0x0000; 
        $grbit2 = 0x0001; 
        $Reserved5 = 0x0000; 

        $header = pack('vv', $record, $length);
        $data = pack('V', $cObj);
        $data .= pack('v', $OT);
        $data .= pack('v', $id);
        $data .= pack('v', $grbit);
        $data .= pack('v', $colL);
        $data .= pack('v', $dxL);
        $data .= pack('v', $rwT);
        $data .= pack('v', $dyT);
        $data .= pack('v', $colR);
        $data .= pack('v', $dxR);
        $data .= pack('v', $rwB);
        $data .= pack('v', $dyB);
        $data .= pack('v', $cbMacro);
        $data .= pack('V', $Reserved1);
        $data .= pack('v', $Reserved2);
        $data .= pack('C', $icvBack);
        $data .= pack('C', $icvFore);
        $data .= pack('C', $fls);
        $data .= pack('C', $fAuto);
        $data .= pack('C', $icv);
        $data .= pack('C', $lns);
        $data .= pack('C', $lnw);
        $data .= pack('C', $fAutoB);
        $data .= pack('v', $frs);
        $data .= pack('V', $cf);
        $data .= pack('v', $Reserved3);
        $data .= pack('v', $cbPictFmla);
        $data .= pack('v', $Reserved4);
        $data .= pack('v', $grbit2);
        $data .= pack('V', $Reserved5);

        $this->append($header . $data);
    }

    
    public function processBitmapGd(GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $data = pack('Vvvvv', 0x000C, $width, $height, 0x01, 0x18);
        for ($j = $height; --$j;) {
            for ($i = 0; $i < $width; ++$i) {
                $colorAt = imagecolorat($image, $i, $j);
                if ($colorAt !== false) {
                    $color = imagecolorsforindex($image, $colorAt);
                    foreach (['red', 'green', 'blue'] as $key) {
                        $color[$key] = $color[$key] + (int) round((255 - $color[$key]) * $color['alpha'] / 127);
                    }
                    $data .= chr($color['blue']) . chr($color['green']) . chr($color['red']);
                }
            }
            if (3 * $width % 4) {
                $data .= str_repeat("\x00", 4 - 3 * $width % 4);
            }
        }

        return [$width, $height, strlen($data), $data];
    }

    
    public function processBitmap(string $bitmap): array
    {
        
        $bmp_fd = @fopen($bitmap, 'rb');
        if ($bmp_fd === false || 0 === (int) filesize($bitmap)) {
            throw new WriterException("Couldn't import $bitmap");
        }

        
        $data = (string) fread($bmp_fd, (int) filesize($bitmap));

        
        if (strlen($data) <= 0x36) {
            throw new WriterException("$bitmap doesn't contain enough data.\n");
        }

        

        $identity = unpack('A2ident', $data);
        if ($identity === false || $identity['ident'] != 'BM') {
            throw new WriterException("$bitmap doesn't appear to be a valid bitmap image.\n");
        }

        
        $data = substr($data, 2);

        
        
        
        $size_array = unpack('Vsa', substr($data, 0, 4)) ?: [];
        
        $size = $size_array['sa'];
        $data = substr($data, 4);
        $size -= 0x36; 
        $size += 0x0C; 

        
        $data = substr($data, 12);

        
        $width_and_height = unpack('V2', substr($data, 0, 8)) ?: [];
        $width = $width_and_height[1];
        $height = $width_and_height[2];
        $data = substr($data, 8);
        if ($width > 0xFFFF) {
            throw new WriterException("$bitmap: largest image width supported is 65k.\n");
        }
        if ($height > 0xFFFF) {
            throw new WriterException("$bitmap: largest image height supported is 65k.\n");
        }

        
        $planes_and_bitcount = unpack('v2', substr($data, 0, 4));
        $data = substr($data, 4);
        if ($planes_and_bitcount === false || $planes_and_bitcount[2] != 24) { 
            throw new WriterException("$bitmap isn't a 24bit true color bitmap.\n");
        }
        if ($planes_and_bitcount[1] != 1) {
            throw new WriterException("$bitmap: only 1 plane supported in bitmap image.\n");
        }

        
        $compression = unpack('Vcomp', substr($data, 0, 4));
        $data = substr($data, 4);

        if ($compression === false || $compression['comp'] != 0) {
            throw new WriterException("$bitmap: compression not supported in bitmap image.\n");
        }

        
        $data = substr($data, 20);

        
        $header = pack('Vvvvv', 0x000C, $width, $height, 0x01, 0x18);
        $data = $header . $data;

        return [$width, $height, $size, $data];
    }

    
    private function writeZoom(): void
    {
        
        if ($this->phpSheet->getSheetView()->getZoomScale() == 100) {
            return;
        }

        $record = 0x00A0; 
        $length = 0x0004; 

        $header = pack('vv', $record, $length);
        $data = pack('vv', $this->phpSheet->getSheetView()->getZoomScale(), 100);
        $this->append($header . $data);
    }

    
    public function getEscher(): ?\PhpOffice\PhpSpreadsheet\Shared\Escher
    {
        return $this->escher;
    }

    
    public function setEscher(?\PhpOffice\PhpSpreadsheet\Shared\Escher $escher): void
    {
        $this->escher = $escher;
    }

    
    private function writeMsoDrawing(): void
    {
        
        if (isset($this->escher)) {
            $writer = new Escher($this->escher);
            $data = $writer->close();
            $spOffsets = $writer->getSpOffsets();
            $spTypes = $writer->getSpTypes();
            

            
            $spOffsets[0] = 0;
            $nm = count($spOffsets) - 1; 
            for ($i = 1; $i <= $nm; ++$i) {
                
                $record = 0x00EC; 

                
                $dataChunk = substr($data, $spOffsets[$i - 1], $spOffsets[$i] - $spOffsets[$i - 1]);

                $length = strlen($dataChunk);
                $header = pack('vv', $record, $length);

                $this->append($header . $dataChunk);

                
                $record = 0x005D; 
                $objData = '';

                
                if ($spTypes[$i] == 0x00C9) {
                    
                    $objData
                        .= pack(
                            'vvvvvVVV',
                            0x0015, 
                            0x0012, 
                            0x0014, 
                            $i, 
                            0x2101, 
                            0, 
                            0, 
                            0  
                        );

                    
                    $objData .= pack('vv', 0x00C, 0x0014);
                    $objData .= pack('H*', '0000000000000000640001000A00000010000100');
                    
                    $objData .= pack('vv', 0x0013, 0x1FEE);
                    $objData .= pack('H*', '00000000010001030000020008005700');
                } else {
                    
                    $objData
                        .= pack(
                            'vvvvvVVV',
                            0x0015, 
                            0x0012, 
                            0x0008, 
                            $i, 
                            0x6011, 
                            0, 
                            0, 
                            0  
                        );
                }

                
                $objData
                    .= pack(
                        'vv',
                        0x0000, 
                        0x0000  
                    );

                $length = strlen($objData);
                $header = pack('vv', $record, $length);
                $this->append($header . $objData);
            }
        }
    }

    
    private function writeDataValidity(): void
    {
        
        $dataValidationCollection1 = $this->phpSheet->getDataValidationCollection();
        $dataValidationCollection = [];
        foreach ($dataValidationCollection1 as $key => $dataValidation) {
            $keyParts = explode(' ', $key);
            foreach ($keyParts as $keyPart) {
                $dataValidationCollection[$keyPart] = $dataValidation;
            }
        }

        
        if (!empty($dataValidationCollection)) {
            
            $record = 0x01B2; 
            $length = 0x0012; 

            $grbit = 0x0000; 
            $horPos = 0x00000000; 
            $verPos = 0x00000000; 
            $objId = 0xFFFFFFFF; 

            $header = pack('vv', $record, $length);
            $data = pack('vVVVV', $grbit, $horPos, $verPos, $objId, count($dataValidationCollection));
            $this->append($header . $data);

            
            $record = 0x01BE; 

            foreach ($dataValidationCollection as $cellCoordinate => $dataValidation) {
                
                $options = 0x00000000;

                
                $type = CellDataValidation::type($dataValidation);

                $options |= $type << 0;

                
                $errorStyle = CellDataValidation::errorStyle($dataValidation);

                $options |= $errorStyle << 4;

                
                if ($type == 0x03 && Preg::isMatch('/^\".*\"$/', $dataValidation->getFormula1())) {
                    $options |= 0x01 << 7;
                }

                
                $options |= $dataValidation->getAllowBlank() << 8;

                
                $options |= (!$dataValidation->getShowDropDown()) << 9;

                
                $options |= $dataValidation->getShowInputMessage() << 18;

                
                $options |= $dataValidation->getShowErrorMessage() << 19;

                
                $operator = CellDataValidation::operator($dataValidation);

                $options |= $operator << 20;

                $data = pack('V', $options);

                
                $promptTitle = $dataValidation->getPromptTitle() !== ''
                    ? $dataValidation->getPromptTitle() : chr(0);
                $data .= StringHelper::UTF8toBIFF8UnicodeLong($promptTitle);

                
                $errorTitle = $dataValidation->getErrorTitle() !== ''
                    ? $dataValidation->getErrorTitle() : chr(0);
                $data .= StringHelper::UTF8toBIFF8UnicodeLong($errorTitle);

                
                $prompt = $dataValidation->getPrompt() !== ''
                    ? $dataValidation->getPrompt() : chr(0);
                $data .= StringHelper::UTF8toBIFF8UnicodeLong($prompt);

                
                $error = $dataValidation->getError() !== ''
                    ? $dataValidation->getError() : chr(0);
                $data .= StringHelper::UTF8toBIFF8UnicodeLong($error);

                
                try {
                    $formula1 = $dataValidation->getFormula1();
                    if ($type == 0x03) { 
                        $formula1 = str_replace(',', chr(0), $formula1);
                    }
                    $this->parser->parse($formula1);
                    $formula1 = $this->parser->toReversePolish();
                    $sz1 = strlen($formula1);
                } catch (PhpSpreadsheetException $e) {
                    $sz1 = 0;
                    $formula1 = '';
                }
                $data .= pack('vv', $sz1, 0x0000);
                $data .= $formula1;

                
                try {
                    $formula2 = $dataValidation->getFormula2();
                    if ($formula2 === '') {
                        throw new WriterException('No formula2');
                    }
                    $this->parser->parse($formula2);
                    $formula2 = $this->parser->toReversePolish();
                    $sz2 = strlen($formula2);
                } catch (PhpSpreadsheetException) {
                    $sz2 = 0;
                    $formula2 = '';
                }
                $data .= pack('vv', $sz2, 0x0000);
                $data .= $formula2;

                
                $data .= pack('v', 0x0001);
                $data .= $this->writeBIFF8CellRangeAddressFixed($cellCoordinate);

                $length = strlen($data);
                $header = pack('vv', $record, $length);

                $this->append($header . $data);
            }
        }
    }

    
    private function writePageLayoutView(): void
    {
        $record = 0x088B; 
        $length = 0x0010; 

        $rt = 0x088B; 
        $grbitFrt = 0x0000; 
        
        $wScalvePLV = $this->phpSheet->getSheetView()->getZoomScale(); 

        
        if ($this->phpSheet->getSheetView()->getView() == SheetView::SHEETVIEW_PAGE_LAYOUT) {
            $fPageLayoutView = 1;
        } else {
            $fPageLayoutView = 0;
        }
        $fRulerVisible = 0;
        $fWhitespaceHidden = 0;

        $grbit = $fPageLayoutView; 
        $grbit |= $fRulerVisible << 1;
        $grbit |= $fWhitespaceHidden << 3;

        $header = pack('vv', $record, $length);
        $data = pack('vvVVvv', $rt, $grbitFrt, 0x00000000, 0x00000000, $wScalvePLV, $grbit);
        $this->append($header . $data);
    }

    
    private function writeCFRule(
        ConditionalHelper $conditionalFormulaHelper,
        Conditional $conditional,
        string $cellRange
    ): void {
        $record = 0x01B1; 
        $type = null; 
        $operatorType = null; 

        if ($conditional->getConditionType() == Conditional::CONDITION_EXPRESSION) {
            $type = 0x02;
            $operatorType = 0x00;
        } elseif ($conditional->getConditionType() == Conditional::CONDITION_CELLIS) {
            $type = 0x01;

            switch ($conditional->getOperatorType()) {
                case Conditional::OPERATOR_NONE:
                    $operatorType = 0x00;

                    break;
                case Conditional::OPERATOR_EQUAL:
                    $operatorType = 0x03;

                    break;
                case Conditional::OPERATOR_GREATERTHAN:
                    $operatorType = 0x05;

                    break;
                case Conditional::OPERATOR_GREATERTHANOREQUAL:
                    $operatorType = 0x07;

                    break;
                case Conditional::OPERATOR_LESSTHAN:
                    $operatorType = 0x06;

                    break;
                case Conditional::OPERATOR_LESSTHANOREQUAL:
                    $operatorType = 0x08;

                    break;
                case Conditional::OPERATOR_NOTEQUAL:
                    $operatorType = 0x04;

                    break;
                case Conditional::OPERATOR_BETWEEN:
                    $operatorType = 0x01;

                    break;
                    
            }
        }

        
        
        $arrConditions = $conditional->getConditions();
        $numConditions = count($arrConditions);

        $szValue1 = 0x0000;
        $szValue2 = 0x0000;
        $operand1 = null;
        $operand2 = null;

        if ($numConditions === 1) {
            $conditionalFormulaHelper->processCondition($arrConditions[0], $cellRange);
            $szValue1 = $conditionalFormulaHelper->size();
            $operand1 = $conditionalFormulaHelper->tokens();
        } elseif ($numConditions === 2 && ($conditional->getOperatorType() === Conditional::OPERATOR_BETWEEN)) {
            $conditionalFormulaHelper->processCondition($arrConditions[0], $cellRange);
            $szValue1 = $conditionalFormulaHelper->size();
            $operand1 = $conditionalFormulaHelper->tokens();
            $conditionalFormulaHelper->processCondition($arrConditions[1], $cellRange);
            $szValue2 = $conditionalFormulaHelper->size();
            $operand2 = $conditionalFormulaHelper->tokens();
        }

        
        
        
        
        
        
        $bBorderLeft = ($conditional->getStyle()->getBorders()->getLeft()->getBorderStyle() !== Border::BORDER_OMIT) ? 1 : 0;
        $bBorderRight = ($conditional->getStyle()->getBorders()->getRight()->getBorderStyle() !== Border::BORDER_OMIT) ? 1 : 0;
        $bBorderTop = ($conditional->getStyle()->getBorders()->getTop()->getBorderStyle() !== Border::BORDER_OMIT) ? 1 : 0;
        $bBorderBottom = ($conditional->getStyle()->getBorders()->getBottom()->getBorderStyle() !== Border::BORDER_OMIT) ? 1 : 0;
        
        
        $bBorderDiagTop = self::$always0; 
        $bBorderDiagBottom = self::$always0; 

        if ($bBorderLeft === 1 || $bBorderRight === 1 || $bBorderTop === 1 || $bBorderBottom === 1 || $bBorderDiagTop === 1 || $bBorderDiagBottom === 1) {
            $bFormatBorder = 1;
        } else {
            $bFormatBorder = 0;
        }
        
        $bFillStyle = $conditional->getStyle()->getFill()->getFillType() ? 1 : 0;
        $bFillColor = $conditional->getStyle()->getFill()->getStartColor()->getARGB() ? 1 : 0;
        $bFillColorBg = $conditional->getStyle()->getFill()->getEndColor()->getARGB() ? 1 : 0;
        if ($bFillStyle == 1 || $bFillColor == 1 || $bFillColorBg == 1) {
            $bFormatFill = 1;
        } else {
            $bFormatFill = 0;
        }
        
        if (
            $conditional->getStyle()->getFont()->getName() !== null
            || $conditional->getStyle()->getFont()->getSize() !== null
            || $conditional->getStyle()->getFont()->getBold() !== null
            || $conditional->getStyle()->getFont()->getItalic() !== null
            || $conditional->getStyle()->getFont()->getSuperscript() !== null
            || $conditional->getStyle()->getFont()->getSubscript() !== null
            || $conditional->getStyle()->getFont()->getUnderline() !== null
            || $conditional->getStyle()->getFont()->getStrikethrough() !== null
            || $conditional->getStyle()->getFont()->getColor()->getARGB() !== null
        ) {
            $bFormatFont = 1;
        } else {
            $bFormatFont = 0;
        }
        
        $flags = 0;
        
        
        
        
        
        $flags |= (1 == self::$always1 ? 0x00000010 : 0);
        
        
        
        $flags |= (1 == self::$always1 ? 0x00000080 : 0);
        
        
        
        
        $flags |= (0 == $bBorderLeft ? 0x00000400 : 0);
        $flags |= (0 == $bBorderRight ? 0x00000800 : 0);
        $flags |= (0 == $bBorderTop ? 0x00001000 : 0);
        $flags |= (0 == $bBorderBottom ? 0x00002000 : 0);
        $flags |= (0 === $bBorderDiagTop ? 0x00004000 : 0); 
        $flags |= (0 === $bBorderDiagBottom ? 0x00008000 : 0); 
        
        $flags |= (1 == $bFillStyle ? 0x00010000 : 0);
        $flags |= (1 == $bFillColor ? 0x00020000 : 0);
        $flags |= (1 == $bFillColorBg ? 0x00040000 : 0);
        $flags |= (1 == self::$always1 ? 0x00380000 : 0);
        
        $flags |= (1 == $bFormatFont ? 0x04000000 : 0);
        
        
        
        $flags |= (1 == $bFormatBorder ? 0x10000000 : 0);
        
        $flags |= (1 == $bFormatFill ? 0x20000000 : 0);
        
        
        
        $flags |= (1 == self::$always0 ? 0x80000000 : 0);

        $dataBlockFont = null;
        
        $dataBlockBorder = null;
        $dataBlockFill = null;

        
        if ($bFormatFont == 1) {
            
            if ($conditional->getStyle()->getFont()->getName() === null) {
                $dataBlockFont = pack('VVVVVVVV', 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000);
                $dataBlockFont .= pack('VVVVVVVV', 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000, 0x00000000);
            } else {
                $dataBlockFont = StringHelper::UTF8toBIFF8UnicodeLong($conditional->getStyle()->getFont()->getName());
            }
            
            if ($conditional->getStyle()->getFont()->getSize() === null) {
                $dataBlockFont .= pack('V', 20 * 11);
            } else {
                $dataBlockFont .= pack('V', 20 * $conditional->getStyle()->getFont()->getSize());
            }
            
            $italicStrike = 0;
            if ($conditional->getStyle()->getFont()->getItalic() === true) {
                $italicStrike |= 2;
            }
            if ($conditional->getStyle()->getFont()->getStrikethrough() === true) {
                $italicStrike |= 0x80;
            }
            $dataBlockFont .= pack('V', $italicStrike);
            
            if ($conditional->getStyle()->getFont()->getBold() === true) {
                $dataBlockFont .= pack('v', 0x02BC);
            } elseif ($conditional->getStyle()->getFont()->getBold() === null) {
                $dataBlockFont .= pack('v', 0x0000);
            } else {
                $dataBlockFont .= pack('v', 0x0190);
            }
            
            if ($conditional->getStyle()->getFont()->getSubscript() === true) {
                $dataBlockFont .= pack('v', 0x02);
                $fontEscapement = 0;
            } elseif ($conditional->getStyle()->getFont()->getSuperscript() === true) {
                $dataBlockFont .= pack('v', 0x01);
                $fontEscapement = 0;
            } else {
                $dataBlockFont .= pack('v', 0x00);
                $fontEscapement = 1;
            }
            
            switch ($conditional->getStyle()->getFont()->getUnderline()) {
                case \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE:
                    $dataBlockFont .= pack('C', 0x00);
                    $fontUnderline = 0;

                    break;
                case \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_DOUBLE:
                    $dataBlockFont .= pack('C', 0x02);
                    $fontUnderline = 0;

                    break;
                case \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_DOUBLEACCOUNTING:
                    $dataBlockFont .= pack('C', 0x22);
                    $fontUnderline = 0;

                    break;
                case \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE:
                    $dataBlockFont .= pack('C', 0x01);
                    $fontUnderline = 0;

                    break;
                case \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLEACCOUNTING:
                    $dataBlockFont .= pack('C', 0x21);
                    $fontUnderline = 0;

                    break;
                default:
                    $dataBlockFont .= pack('C', 0x00);
                    $fontUnderline = 1;

                    break;
            }
            
            $dataBlockFont .= pack('vC', 0x0000, 0x00);
            
            $colorIdx = $this->workbookColorIndex($conditional->getStyle()->getFont()->getColor()->getRgb(), 0);
            $dataBlockFont .= pack('V', $colorIdx);
            
            $dataBlockFont .= pack('V', 0x00000000);
            
            $optionsFlags = 0;
            $optionsFlags |= ($conditional->getStyle()->getFont()->getBold() === null && $conditional->getStyle()->getFont()->getItalic() === null) ? 2 : 0;
            $optionsFlags |= (1 == self::$always1 ? 0x00000008 : 0);
            $optionsFlags |= (1 == self::$always1 ? 0x00000010 : 0);
            $optionsFlags |= (1 == self::$always0 ? 0x00000020 : 0);
            $optionsFlags |= ($conditional->getStyle()->getFont()->getStrikethrough() === null) ? 0x80 : 0;
            $dataBlockFont .= pack('V', $optionsFlags);
            
            $dataBlockFont .= pack('V', $fontEscapement);
            
            $dataBlockFont .= pack('V', $fontUnderline);
            
            $dataBlockFont .= pack('V', 0x00000000);
            
            $dataBlockFont .= pack('V', 0x00000000);
            
            $dataBlockFont .= pack('VV', 0x00000000, 0x00000000);
            
            $dataBlockFont .= pack('v', 0x0001);
        }
        
        if ($bFormatBorder === 1) {
            $blockLineStyle = Style\CellBorder::style($conditional->getStyle()->getBorders()->getLeft());
            $blockLineStyle |= Style\CellBorder::style($conditional->getStyle()->getBorders()->getRight()) << 4;
            $blockLineStyle |= Style\CellBorder::style($conditional->getStyle()->getBorders()->getTop()) << 8;
            $blockLineStyle |= Style\CellBorder::style($conditional->getStyle()->getBorders()->getBottom()) << 12;

            if ($bBorderLeft !== 0) {
                $colorIdx = $this->workbookColorIndex($conditional->getStyle()->getBorders()->getLeft()->getColor()->getRgb(), 0);
                $blockLineStyle |= $colorIdx << 16;
            }
            if ($bBorderRight !== 0) {
                $colorIdx = $this->workbookColorIndex($conditional->getStyle()->getBorders()->getRight()->getColor()->getRgb(), 0);
                $blockLineStyle |= $colorIdx << 23;
            }
            $blockColor = 0;
            if ($bBorderTop !== 0) {
                $colorIdx = $this->workbookColorIndex($conditional->getStyle()->getBorders()->getTop()->getColor()->getRgb(), 0);
                $blockColor |= $colorIdx;
            }
            if ($bBorderBottom !== 0) {
                $colorIdx = $this->workbookColorIndex($conditional->getStyle()->getBorders()->getBottom()->getColor()->getRgb(), 0);
                $blockColor |= $colorIdx << 7;
            }
            
            $dataBlockBorder = pack('VV', $blockLineStyle, $blockColor);
        }
        if ($bFormatFill === 1) {
            
            $blockFillPatternStyle = Style\CellFill::style($conditional->getStyle()->getFill());
            
            $colorIdxBg = $this->workbookColorIndex($conditional->getStyle()->getFill()->getStartColor()->getRgb(), 0x41);
            
            $colorIdxFg = $this->workbookColorIndex($conditional->getStyle()->getFill()->getEndColor()->getRgb(), 0x40);

            $dataBlockFill = pack('v', $blockFillPatternStyle);
            $dataBlockFill .= pack('v', $colorIdxFg | ($colorIdxBg << 7));
        }

        $data = pack('CCvvVv', $type, $operatorType, $szValue1, $szValue2, $flags, 0x0000);
        if ($bFormatFont === 1) { 
            $data .= $dataBlockFont;
        }
        
        
        
        if ($bFormatBorder === 1) {
            $data .= $dataBlockBorder;
        }
        if ($bFormatFill === 1) { 
            $data .= $dataBlockFill;
        }
        
        
        
        if ($operand1 !== null) {
            $data .= $operand1;
        }
        if ($operand2 !== null) {
            $data .= $operand2;
        }
        $header = pack('vv', $record, strlen($data));
        $this->append($header . $data);
    }

    
    private function writeCFHeader(string $cellCoordinate, array $conditionalStyles): bool
    {
        $record = 0x01B0; 
        $length = 0x0016; 

        $numColumnMin = null;
        $numColumnMax = null;
        $numRowMin = null;
        $numRowMax = null;

        $arrConditional = [];
        foreach ($conditionalStyles as $conditional) {
            if (!in_array($conditional->getHashCode(), $arrConditional)) {
                $arrConditional[] = $conditional->getHashCode();
            }
            
            $rangeCoordinates = Coordinate::rangeBoundaries($cellCoordinate);
            if ($numColumnMin === null || ($numColumnMin > $rangeCoordinates[0][0])) {
                $numColumnMin = $rangeCoordinates[0][0];
            }
            if ($numColumnMax === null || ($numColumnMax < $rangeCoordinates[1][0])) {
                $numColumnMax = $rangeCoordinates[1][0];
            }
            if ($numRowMin === null || ($numRowMin > $rangeCoordinates[0][1])) {
                $numRowMin = (int) $rangeCoordinates[0][1];
            }
            if ($numRowMax === null || ($numRowMax < $rangeCoordinates[1][1])) {
                $numRowMax = (int) $rangeCoordinates[1][1];
            }
        }

        if (count($arrConditional) === 0) {
            return false;
        }

        $needRedraw = 1;
        $cellRange = pack('vvvv', $numRowMin - 1, $numRowMax - 1, $numColumnMin - 1, $numColumnMax - 1);

        $header = pack('vv', $record, $length);
        $data = pack('vv', count($arrConditional), $needRedraw);
        $data .= $cellRange;
        $data .= pack('v', 0x0001);
        $data .= $cellRange;
        $this->append($header . $data);

        return true;
    }

    

    private function workbookColorIndex(?string $rgb, int $default): int
    {
        return (empty($rgb) || $this->writerWorkbook === null) ? $default : $this->writerWorkbook->addColor($rgb, $default);
    }
}
