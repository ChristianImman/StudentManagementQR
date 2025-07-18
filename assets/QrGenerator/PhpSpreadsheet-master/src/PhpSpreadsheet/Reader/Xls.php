<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xls\Style\CellFont;
use PhpOffice\PhpSpreadsheet\Reader\Xls\Style\FillPattern;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\CodePage;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\Escher;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\OLE;
use PhpOffice\PhpSpreadsheet\Shared\OLERead;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;































class Xls extends XlsBase
{
    
    protected ?string $summaryInformation = null;

    
    protected ?string $documentSummaryInformation = null;

    
    protected string $data;

    
    protected int $dataSize;

    
    protected int $pos;

    
    protected Spreadsheet $spreadsheet;

    
    protected Worksheet $phpSheet;

    
    protected int $version = 0;

    
    protected array $formats;

    
    protected array $objFonts;

    
    protected array $palette;

    
    protected array $sheets;

    
    protected array $externalBooks;

    
    protected array $ref;

    
    protected array $externalNames;

    
    protected array $definedname;

    
    protected array $sst;

    
    protected bool $frozen;

    
    protected bool $isFitToPages;

    
    protected array $objs;

    
    protected array $textObjects;

    
    protected array $cellNotes;

    
    protected string $drawingGroupData;

    
    protected string $drawingData;

    
    protected int $xfIndex;

    
    protected array $mapCellXfIndex;

    
    protected array $mapCellStyleXfIndex;

    
    protected array $sharedFormulas;

    
    protected array $sharedFormulaParts;

    
    protected int $encryption = 0;

    
    protected int $encryptionStartPos = 0;

    
    protected ?Xls\RC4 $rc4Key = null;

    
    protected int $rc4Pos = 0;

    
    private string $md5Ctxt = '';

    protected int $textObjRef;

    protected string $baseCell;

    protected bool $activeSheetSet = false;

    
    public function listWorksheetNames(string $filename): array
    {
        return (new Xls\ListFunctions())->listWorksheetNames2($filename, $this);
    }

    
    public function listWorksheetInfo(string $filename): array
    {
        return (new Xls\ListFunctions())->listWorksheetInfo2($filename, $this);
    }

    
    protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
        return (new Xls\LoadSpreadsheet())->loadSpreadsheetFromFile2($filename, $this);
    }

    
    protected function readRecordData(string $data, int $pos, int $len): string
    {
        $data = substr($data, $pos, $len);

        
        if ($this->encryption == self::MS_BIFF_CRYPTO_NONE || $pos < $this->encryptionStartPos) {
            return $data;
        }

        $recordData = '';
        if ($this->encryption == self::MS_BIFF_CRYPTO_RC4) {
            $oldBlock = floor($this->rc4Pos / self::REKEY_BLOCK);
            $block = (int) floor($pos / self::REKEY_BLOCK);
            $endBlock = (int) floor(($pos + $len) / self::REKEY_BLOCK);

            
            
            if ($block != $oldBlock || $pos < $this->rc4Pos || !$this->rc4Key) {
                $this->rc4Key = $this->makeKey($block, $this->md5Ctxt);
                $step = $pos % self::REKEY_BLOCK;
            } else {
                $step = $pos - $this->rc4Pos;
            }
            $this->rc4Key->RC4(str_repeat("\0", $step));

            
            while ($block != $endBlock) {
                $step = self::REKEY_BLOCK - ($pos % self::REKEY_BLOCK);
                $recordData .= $this->rc4Key->RC4(substr($data, 0, $step));
                $data = substr($data, $step);
                $pos += $step;
                $len -= $step;
                ++$block;
                $this->rc4Key = $this->makeKey($block, $this->md5Ctxt);
            }
            $recordData .= $this->rc4Key->RC4(substr($data, 0, $len));

            
            
            $this->rc4Pos = $pos + $len;
        } elseif ($this->encryption == self::MS_BIFF_CRYPTO_XOR) {
            throw new Exception('XOr encryption not supported');
        }

        return $recordData;
    }

    
    protected function loadOLE(string $filename): void
    {
        
        $ole = new OLERead();
        
        $ole->read($filename);
        
        $this->data = $ole->getStream($ole->wrkbook); 
        
        $this->summaryInformation = $ole->getStream($ole->summaryInformation);
        
        $this->documentSummaryInformation = $ole->getStream($ole->documentSummaryInformation);
    }

    
    protected function readSummaryInformation(): void
    {
        if (!isset($this->summaryInformation)) {
            return;
        }

        
        
        
        
        
        
        

        
        
        $secOffset = self::getInt4d($this->summaryInformation, 44);

        
        
        

        
        $countProperties = self::getInt4d($this->summaryInformation, $secOffset + 4);

        
        $codePage = 'CP1252';

        
        
        for ($i = 0; $i < $countProperties; ++$i) {
            
            $id = self::getInt4d($this->summaryInformation, ($secOffset + 8) + (8 * $i));

            
            
            $offset = self::getInt4d($this->summaryInformation, ($secOffset + 12) + (8 * $i));

            $type = self::getInt4d($this->summaryInformation, $secOffset + $offset);

            
            $value = null;

            
            switch ($type) {
                case 0x02: 
                    $value = self::getUInt2d($this->summaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x03: 
                    $value = self::getInt4d($this->summaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x13: 
                    
                    break;
                case 0x1E: 
                    $byteLength = self::getInt4d($this->summaryInformation, $secOffset + 4 + $offset);
                    $value = substr($this->summaryInformation, $secOffset + 8 + $offset, $byteLength);
                    $value = StringHelper::convertEncoding($value, 'UTF-8', $codePage);
                    $value = rtrim($value);

                    break;
                case 0x40: 
                    
                    $value = OLE::OLE2LocalDate(substr($this->summaryInformation, $secOffset + 4 + $offset, 8));

                    break;
                case 0x47: 
                    
                    break;
            }

            switch ($id) {
                case 0x01:    
                    $codePage = CodePage::numberToName((int) $value);

                    break;
                case 0x02:    
                    $this->spreadsheet->getProperties()->setTitle("$value");

                    break;
                case 0x03:    
                    $this->spreadsheet->getProperties()->setSubject("$value");

                    break;
                case 0x04:    
                    $this->spreadsheet->getProperties()->setCreator("$value");

                    break;
                case 0x05:    
                    $this->spreadsheet->getProperties()->setKeywords("$value");

                    break;
                case 0x06:    
                    $this->spreadsheet->getProperties()->setDescription("$value");

                    break;
                case 0x07:    
                    
                    break;
                case 0x08:    
                    $this->spreadsheet->getProperties()->setLastModifiedBy("$value");

                    break;
                case 0x09:    
                    
                    break;
                case 0x0A:    
                    
                    break;
                case 0x0B:    
                    
                    break;
                case 0x0C:    
                    $this->spreadsheet->getProperties()->setCreated($value);

                    break;
                case 0x0D:    
                    $this->spreadsheet->getProperties()->setModified($value);

                    break;
                case 0x0E:    
                    
                    break;
                case 0x0F:    
                    
                    break;
                case 0x10:    
                    
                    break;
                case 0x11:    
                    
                    break;
                case 0x12:    
                    
                    break;
                case 0x13:    
                    
                    break;
            }
        }
    }

    
    protected function readDocumentSummaryInformation(): void
    {
        if (!isset($this->documentSummaryInformation)) {
            return;
        }

        
        
        
        
        
        
        

        
        
        $secOffset = self::getInt4d($this->documentSummaryInformation, 44);

        
        
        

        
        $countProperties = self::getInt4d($this->documentSummaryInformation, $secOffset + 4);

        
        $codePage = 'CP1252';

        
        
        for ($i = 0; $i < $countProperties; ++$i) {
            
            $id = self::getInt4d($this->documentSummaryInformation, ($secOffset + 8) + (8 * $i));

            
            
            $offset = self::getInt4d($this->documentSummaryInformation, ($secOffset + 12) + (8 * $i));

            $type = self::getInt4d($this->documentSummaryInformation, $secOffset + $offset);

            
            $value = null;

            
            switch ($type) {
                case 0x02:    
                    $value = self::getUInt2d($this->documentSummaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x03:    
                    $value = self::getInt4d($this->documentSummaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x0B:  
                    $value = self::getUInt2d($this->documentSummaryInformation, $secOffset + 4 + $offset);
                    $value = ($value == 0 ? false : true);

                    break;
                case 0x13:    
                    
                    break;
                case 0x1E:    
                    $byteLength = self::getInt4d($this->documentSummaryInformation, $secOffset + 4 + $offset);
                    $value = substr($this->documentSummaryInformation, $secOffset + 8 + $offset, $byteLength);
                    $value = StringHelper::convertEncoding($value, 'UTF-8', $codePage);
                    $value = rtrim($value);

                    break;
                case 0x40:    
                    
                    $value = OLE::OLE2LocalDate(substr($this->documentSummaryInformation, $secOffset + 4 + $offset, 8));

                    break;
                case 0x47:    
                    
                    break;
            }

            switch ($id) {
                case 0x01:    
                    $codePage = CodePage::numberToName((int) $value);

                    break;
                case 0x02:    
                    $this->spreadsheet->getProperties()->setCategory("$value");

                    break;
                case 0x03:    
                    
                    break;
                case 0x04:    
                    
                    break;
                case 0x05:    
                    
                    break;
                case 0x06:    
                    
                    break;
                case 0x07:    
                    
                    break;
                case 0x08:    
                    
                    break;
                case 0x09:    
                    
                    break;
                case 0x0A:    
                    
                    break;
                case 0x0B:    
                    
                    break;
                case 0x0C:    
                    
                    break;
                case 0x0D:    
                    
                    break;
                case 0x0E:    
                    $this->spreadsheet->getProperties()->setManager("$value");

                    break;
                case 0x0F:    
                    $this->spreadsheet->getProperties()->setCompany("$value");

                    break;
                case 0x10:    
                    
                    break;
            }
        }
    }

    
    protected function readDefault(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);

        
        $this->pos += 4 + $length;
    }

    
    protected function readNote(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        $cellAddress = Xls\Biff8::readBIFF8CellAddress(substr($recordData, 0, 4));
        if ($this->version == self::XLS_BIFF8) {
            $noteObjID = self::getUInt2d($recordData, 6);
            $noteAuthor = self::readUnicodeStringLong(substr($recordData, 8));
            $noteAuthor = $noteAuthor['value'];
            $this->cellNotes[$noteObjID] = [
                'cellRef' => $cellAddress,
                'objectID' => $noteObjID,
                'author' => $noteAuthor,
            ];
        } else {
            $extension = false;
            if ($cellAddress == '$B$65536') {
                
                
                
                
                $extension = true;
                $arrayKeys = array_keys($this->phpSheet->getComments());
                $cellAddress = array_pop($arrayKeys);
            }

            $cellAddress = str_replace('$', '', (string) $cellAddress);
            
            $noteText = trim(substr($recordData, 6));

            if ($extension) {
                
                $comment = $this->phpSheet->getComment($cellAddress);
                $commentText = $comment->getText()->getPlainText();
                $comment->setText($this->parseRichText($commentText . $noteText));
            } else {
                
                $this->phpSheet->getComment($cellAddress)->setText($this->parseRichText($noteText));

            }
        }
    }

    
    protected function readTextObject(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        
        
        
        
        
        
        $grbitOpts = self::getUInt2d($recordData, 0);
        $rot = self::getUInt2d($recordData, 2);
        
        $cbRuns = self::getUInt2d($recordData, 12);
        $text = $this->getSplicedRecordData();

        $textByte = $text['spliceOffsets'][1] - $text['spliceOffsets'][0] - 1;
        $textStr = substr($text['recordData'], $text['spliceOffsets'][0] + 1, $textByte);
        
        $is16Bit = ord($text['recordData'][0]);
        
        
        if (($is16Bit & 0x01) === 0) {
            $textStr = StringHelper::ConvertEncoding($textStr, 'UTF-8', 'ISO-8859-1');
        } else {
            $textStr = $this->decodeCodepage($textStr);
        }

        $this->textObjects[$this->textObjRef] = [
            'text' => $textStr,
            'format' => substr($text['recordData'], $text['spliceOffsets'][1], $cbRuns),
            'alignment' => $grbitOpts,
            'rotation' => $rot,
        ];
    }

    
    protected function readBof(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = substr($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $substreamType = self::getUInt2d($recordData, 2);

        switch ($substreamType) {
            case self::XLS_WORKBOOKGLOBALS:
                $version = self::getUInt2d($recordData, 0);
                if (($version != self::XLS_BIFF8) && ($version != self::XLS_BIFF7)) {
                    throw new Exception('Cannot read this Excel file. Version is too old.');
                }
                $this->version = $version;

                break;
            case self::XLS_WORKSHEET:
                
                
                break;
            default:
                
                
                do {
                    $code = self::getUInt2d($this->data, $this->pos);
                    $this->readDefault();
                } while ($code != self::XLS_TYPE_EOF && $this->pos < $this->dataSize);

                break;
        }
    }

    
    protected function readFilepass(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);

        if ($length != 54) {
            throw new Exception('Unexpected file pass record length');
        }

        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->verifyPassword('VelvetSweatshop', substr($recordData, 6, 16), substr($recordData, 22, 16), substr($recordData, 38, 16), $this->md5Ctxt)) {
            throw new Exception('Decryption password incorrect');
        }

        $this->encryption = self::MS_BIFF_CRYPTO_RC4;

        
        $this->encryptionStartPos = $this->pos + self::getUInt2d($this->data, $this->pos + 2);
    }

    
    private function makeKey(int $block, string $valContext): Xls\RC4
    {
        $pwarray = str_repeat("\0", 64);

        for ($i = 0; $i < 5; ++$i) {
            $pwarray[$i] = $valContext[$i];
        }

        $pwarray[5] = chr($block & 0xFF);
        $pwarray[6] = chr(($block >> 8) & 0xFF);
        $pwarray[7] = chr(($block >> 16) & 0xFF);
        $pwarray[8] = chr(($block >> 24) & 0xFF);

        $pwarray[9] = "\x80";
        $pwarray[56] = "\x48";

        $md5 = new Xls\MD5();
        $md5->add($pwarray);

        $s = $md5->getContext();

        return new Xls\RC4($s);
    }

    
    private function verifyPassword(string $password, string $docid, string $salt_data, string $hashedsalt_data, string &$valContext): bool
    {
        $pwarray = str_repeat("\0", 64);

        $iMax = strlen($password);
        for ($i = 0; $i < $iMax; ++$i) {
            $o = ord(substr($password, $i, 1));
            $pwarray[2 * $i] = chr($o & 0xFF);
            $pwarray[2 * $i + 1] = chr(($o >> 8) & 0xFF);
        }
        $pwarray[2 * $i] = chr(0x80);
        $pwarray[56] = chr(($i << 4) & 0xFF);

        $md5 = new Xls\MD5();
        $md5->add($pwarray);

        $mdContext1 = $md5->getContext();

        $offset = 0;
        $keyoffset = 0;
        $tocopy = 5;

        $md5->reset();

        while ($offset != 16) {
            if ((64 - $offset) < 5) {
                $tocopy = 64 - $offset;
            }
            for ($i = 0; $i <= $tocopy; ++$i) {
                $pwarray[$offset + $i] = $mdContext1[$keyoffset + $i];
            }
            $offset += $tocopy;

            if ($offset == 64) {
                $md5->add($pwarray);
                $keyoffset = $tocopy;
                $tocopy = 5 - $tocopy;
                $offset = 0;

                continue;
            }

            $keyoffset = 0;
            $tocopy = 5;
            for ($i = 0; $i < 16; ++$i) {
                $pwarray[$offset + $i] = $docid[$i];
            }
            $offset += 16;
        }

        $pwarray[16] = "\x80";
        for ($i = 0; $i < 47; ++$i) {
            $pwarray[17 + $i] = "\0";
        }
        $pwarray[56] = "\x80";
        $pwarray[57] = "\x0a";

        $md5->add($pwarray);
        $valContext = $md5->getContext();

        $key = $this->makeKey(0, $valContext);

        $salt = $key->RC4($salt_data);
        $hashedsalt = $key->RC4($hashedsalt_data);

        $salt .= "\x80" . str_repeat("\0", 47);
        $salt[56] = "\x80";

        $md5->reset();
        $md5->add($salt);
        $mdContext2 = $md5->getContext();

        return $mdContext2 == $hashedsalt;
    }

    
    protected function readCodepage(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $codepage = self::getUInt2d($recordData, 0);

        $this->codepage = CodePage::numberToName($codepage);
    }

    
    protected function readDateMode(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        Date::setExcelCalendar(Date::CALENDAR_WINDOWS_1900);
        $this->spreadsheet->setExcelCalendar(Date::CALENDAR_WINDOWS_1900);
        if (ord($recordData[0]) == 1) {
            Date::setExcelCalendar(Date::CALENDAR_MAC_1904);
            $this->spreadsheet->setExcelCalendar(Date::CALENDAR_MAC_1904);
        }
    }

    
    protected function readFont(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            $objFont = new Font();

            
            $size = self::getUInt2d($recordData, 0);
            $objFont->setSize($size / 20);

            
            
            
            $isItalic = (0x0002 & self::getUInt2d($recordData, 2)) >> 1;
            if ($isItalic) {
                $objFont->setItalic(true);
            }

            
            
            $isStrike = (0x0008 & self::getUInt2d($recordData, 2)) >> 3;
            if ($isStrike) {
                $objFont->setStrikethrough(true);
            }

            
            $colorIndex = self::getUInt2d($recordData, 4);
            $objFont->colorIndex = $colorIndex;

            
            $weight = self::getUInt2d($recordData, 6); 
            if ($weight >= 550) {
                $objFont->setBold(true);
            }

            
            $escapement = self::getUInt2d($recordData, 8);
            CellFont::escapement($objFont, $escapement);

            
            $underlineType = ord($recordData[10]);
            CellFont::underline($objFont, $underlineType);

            
            
            
            
            if ($this->version == self::XLS_BIFF8) {
                $string = self::readUnicodeStringShort(substr($recordData, 14));
            } else {
                $string = $this->readByteStringShort(substr($recordData, 14));
            }
            $objFont->setName($string['value']);

            $this->objFonts[] = $objFont;
        }
    }

    
    protected function readFormat(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            $indexCode = self::getUInt2d($recordData, 0);

            if ($this->version == self::XLS_BIFF8) {
                $string = self::readUnicodeStringLong(substr($recordData, 2));
            } else {
                
                $string = $this->readByteStringShort(substr($recordData, 2));
            }

            $formatString = $string['value'];
            
            if ($formatString === 'GENERAL') {
                $formatString = NumberFormat::FORMAT_GENERAL;
            }
            $this->formats[$indexCode] = $formatString;
        }
    }

    
    protected function readXf(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        $objStyle = new Style();

        if (!$this->readDataOnly) {
            
            if (self::getUInt2d($recordData, 0) < 4) {
                $fontIndex = self::getUInt2d($recordData, 0);
            } else {
                
                
                $fontIndex = self::getUInt2d($recordData, 0) - 1;
            }
            if (isset($this->objFonts[$fontIndex])) {
                $objStyle->setFont($this->objFonts[$fontIndex]);
            }

            
            $numberFormatIndex = self::getUInt2d($recordData, 2);
            if (isset($this->formats[$numberFormatIndex])) {
                
                $numberFormat = ['formatCode' => $this->formats[$numberFormatIndex]];
            } elseif (($code = NumberFormat::builtInFormatCode($numberFormatIndex)) !== '') {
                
                $numberFormat = ['formatCode' => $code];
            } else {
                
                $numberFormat = ['formatCode' => NumberFormat::FORMAT_GENERAL];
            }
            $objStyle->getNumberFormat()->setFormatCode($numberFormat['formatCode']);

            
            
            $xfTypeProt = self::getUInt2d($recordData, 4);
            
            $isLocked = (0x01 & $xfTypeProt) >> 0;
            $objStyle->getProtection()->setLocked($isLocked ? Protection::PROTECTION_INHERIT : Protection::PROTECTION_UNPROTECTED);

            
            $isHidden = (0x02 & $xfTypeProt) >> 1;
            $objStyle->getProtection()->setHidden($isHidden ? Protection::PROTECTION_PROTECTED : Protection::PROTECTION_UNPROTECTED);

            
            $isCellStyleXf = (0x04 & $xfTypeProt) >> 2;

            
            
            $horAlign = (0x07 & ord($recordData[6])) >> 0;
            Xls\Style\CellAlignment::horizontal($objStyle->getAlignment(), $horAlign);

            
            $wrapText = (0x08 & ord($recordData[6])) >> 3;
            Xls\Style\CellAlignment::wrap($objStyle->getAlignment(), $wrapText);

            
            $vertAlign = (0x70 & ord($recordData[6])) >> 4;
            Xls\Style\CellAlignment::vertical($objStyle->getAlignment(), $vertAlign);

            if ($this->version == self::XLS_BIFF8) {
                
                $angle = ord($recordData[7]);
                $rotation = 0;
                if ($angle <= 90) {
                    $rotation = $angle;
                } elseif ($angle <= 180) {
                    $rotation = 90 - $angle;
                } elseif ($angle == Alignment::TEXTROTATION_STACK_EXCEL) {
                    $rotation = Alignment::TEXTROTATION_STACK_PHPSPREADSHEET;
                }
                $objStyle->getAlignment()->setTextRotation($rotation);

                
                
                $indent = (0x0F & ord($recordData[8])) >> 0;
                $objStyle->getAlignment()->setIndent($indent);

                
                $shrinkToFit = (0x10 & ord($recordData[8])) >> 4;
                switch ($shrinkToFit) {
                    case 0:
                        $objStyle->getAlignment()->setShrinkToFit(false);

                        break;
                    case 1:
                        $objStyle->getAlignment()->setShrinkToFit(true);

                        break;
                }

                

                
                
                if ($bordersLeftStyle = Xls\Style\Border::lookup((0x0000000F & self::getInt4d($recordData, 10)) >> 0)) {
                    $objStyle->getBorders()->getLeft()->setBorderStyle($bordersLeftStyle);
                }
                
                if ($bordersRightStyle = Xls\Style\Border::lookup((0x000000F0 & self::getInt4d($recordData, 10)) >> 4)) {
                    $objStyle->getBorders()->getRight()->setBorderStyle($bordersRightStyle);
                }
                
                if ($bordersTopStyle = Xls\Style\Border::lookup((0x00000F00 & self::getInt4d($recordData, 10)) >> 8)) {
                    $objStyle->getBorders()->getTop()->setBorderStyle($bordersTopStyle);
                }
                
                if ($bordersBottomStyle = Xls\Style\Border::lookup((0x0000F000 & self::getInt4d($recordData, 10)) >> 12)) {
                    $objStyle->getBorders()->getBottom()->setBorderStyle($bordersBottomStyle);
                }
                
                $objStyle->getBorders()->getLeft()->colorIndex = (0x007F0000 & self::getInt4d($recordData, 10)) >> 16;

                
                $objStyle->getBorders()->getRight()->colorIndex = (0x3F800000 & self::getInt4d($recordData, 10)) >> 23;

                
                $diagonalDown = (0x40000000 & self::getInt4d($recordData, 10)) >> 30 ? true : false;

                
                $diagonalUp = (self::HIGH_ORDER_BIT & self::getInt4d($recordData, 10)) >> 31 ? true : false;

                if ($diagonalUp === false) {
                    if ($diagonalDown === false) {
                        $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_NONE);
                    } else {
                        $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_DOWN);
                    }
                } elseif ($diagonalDown === false) {
                    $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_UP);
                } else {
                    $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_BOTH);
                }

                
                
                $objStyle->getBorders()->getTop()->colorIndex = (0x0000007F & self::getInt4d($recordData, 14)) >> 0;

                
                $objStyle->getBorders()->getBottom()->colorIndex = (0x00003F80 & self::getInt4d($recordData, 14)) >> 7;

                
                $objStyle->getBorders()->getDiagonal()->colorIndex = (0x001FC000 & self::getInt4d($recordData, 14)) >> 14;

                
                if ($bordersDiagonalStyle = Xls\Style\Border::lookup((0x01E00000 & self::getInt4d($recordData, 14)) >> 21)) {
                    $objStyle->getBorders()->getDiagonal()->setBorderStyle($bordersDiagonalStyle);
                }

                
                if ($fillType = FillPattern::lookup((self::FC000000 & self::getInt4d($recordData, 14)) >> 26)) {
                    $objStyle->getFill()->setFillType($fillType);
                }
                
                
                $objStyle->getFill()->startcolorIndex = (0x007F & self::getUInt2d($recordData, 18)) >> 0;

                
                $objStyle->getFill()->endcolorIndex = (0x3F80 & self::getUInt2d($recordData, 18)) >> 7;
            } else {
                

                
                $orientationAndFlags = ord($recordData[7]);

                
                $xfOrientation = (0x03 & $orientationAndFlags) >> 0;
                switch ($xfOrientation) {
                    case 0:
                        $objStyle->getAlignment()->setTextRotation(0);

                        break;
                    case 1:
                        $objStyle->getAlignment()->setTextRotation(Alignment::TEXTROTATION_STACK_PHPSPREADSHEET);

                        break;
                    case 2:
                        $objStyle->getAlignment()->setTextRotation(90);

                        break;
                    case 3:
                        $objStyle->getAlignment()->setTextRotation(-90);

                        break;
                }

                
                $borderAndBackground = self::getInt4d($recordData, 8);

                
                $objStyle->getFill()->startcolorIndex = (0x0000007F & $borderAndBackground) >> 0;

                
                $objStyle->getFill()->endcolorIndex = (0x00003F80 & $borderAndBackground) >> 7;

                
                $objStyle->getFill()->setFillType(FillPattern::lookup((0x003F0000 & $borderAndBackground) >> 16));

                
                $objStyle->getBorders()->getBottom()->setBorderStyle(Xls\Style\Border::lookup((0x01C00000 & $borderAndBackground) >> 22));

                
                $objStyle->getBorders()->getBottom()->colorIndex = (self::FE000000 & $borderAndBackground) >> 25;

                
                $borderLines = self::getInt4d($recordData, 12);

                
                $objStyle->getBorders()->getTop()->setBorderStyle(Xls\Style\Border::lookup((0x00000007 & $borderLines) >> 0));

                
                $objStyle->getBorders()->getLeft()->setBorderStyle(Xls\Style\Border::lookup((0x00000038 & $borderLines) >> 3));

                
                $objStyle->getBorders()->getRight()->setBorderStyle(Xls\Style\Border::lookup((0x000001C0 & $borderLines) >> 6));

                
                $objStyle->getBorders()->getTop()->colorIndex = (0x0000FE00 & $borderLines) >> 9;

                
                $objStyle->getBorders()->getLeft()->colorIndex = (0x007F0000 & $borderLines) >> 16;

                
                $objStyle->getBorders()->getRight()->colorIndex = (0x3F800000 & $borderLines) >> 23;
            }

            
            if ($isCellStyleXf) {
                
                if ($this->xfIndex == 0) {
                    $this->spreadsheet->addCellStyleXf($objStyle);
                    $this->mapCellStyleXfIndex[$this->xfIndex] = 0;
                }
            } else {
                
                $this->spreadsheet->addCellXf($objStyle);
                $this->mapCellXfIndex[$this->xfIndex] = count($this->spreadsheet->getCellXfCollection()) - 1;
            }

            
            ++$this->xfIndex;
        }
    }

    protected function readXfExt(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            

            

            

            

            
            $ixfe = self::getUInt2d($recordData, 14);

            

            
            

            
            $offset = 20;
            while ($offset < $length) {
                
                $extType = self::getUInt2d($recordData, $offset);

                
                $cb = self::getUInt2d($recordData, $offset + 2);

                
                $extData = substr($recordData, $offset + 4, $cb);

                switch ($extType) {
                    case 4:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $fill = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getFill();
                                $fill->getStartColor()->setRGB($rgb);
                                $fill->startcolorIndex = null; 
                            }
                        }

                        break;
                    case 5:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $fill = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getFill();
                                $fill->getEndColor()->setRGB($rgb);
                                $fill->endcolorIndex = null; 
                            }
                        }

                        break;
                    case 7:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $top = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getTop();
                                $top->getColor()->setRGB($rgb);
                                $top->colorIndex = null; 
                            }
                        }

                        break;
                    case 8:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $bottom = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getBottom();
                                $bottom->getColor()->setRGB($rgb);
                                $bottom->colorIndex = null; 
                            }
                        }

                        break;
                    case 9:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $left = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getLeft();
                                $left->getColor()->setRGB($rgb);
                                $left->colorIndex = null; 
                            }
                        }

                        break;
                    case 10:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $right = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getRight();
                                $right->getColor()->setRGB($rgb);
                                $right->colorIndex = null; 
                            }
                        }

                        break;
                    case 11:        
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $diagonal = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getDiagonal();
                                $diagonal->getColor()->setRGB($rgb);
                                $diagonal->colorIndex = null; 
                            }
                        }

                        break;
                    case 13:    
                        $xclfType = self::getUInt2d($extData, 0); 
                        $xclrValue = substr($extData, 4, 4); 

                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                            
                            if (isset($this->mapCellXfIndex[$ixfe])) {
                                $font = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getFont();
                                $font->getColor()->setRGB($rgb);
                                $font->colorIndex = null; 
                            }
                        }

                        break;
                }

                $offset += $cb;
            }
        }
    }

    
    protected function readStyle(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $ixfe = self::getUInt2d($recordData, 0);

            
            

            
            $isBuiltIn = (bool) ((0x8000 & $ixfe) >> 15);

            if ($isBuiltIn) {
                
                $builtInId = ord($recordData[2]);

                switch ($builtInId) {
                    case 0x00:
                        
                        break;
                    default:
                        break;
                }
            }
            
        }
    }

    
    protected function readPalette(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $nm = self::getUInt2d($recordData, 0);

            
            for ($i = 0; $i < $nm; ++$i) {
                $rgb = substr($recordData, 2 + 4 * $i, 4);
                $this->palette[] = self::readRGB($rgb);
            }
        }
    }

    
    protected function readSheet(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        
        $rec_offset = self::getInt4d($this->data, $this->pos + 4);

        
        $this->pos += 4 + $length;

        
        $sheetState = match (ord($recordData[4])) {
            0x00 => Worksheet::SHEETSTATE_VISIBLE,
            0x01 => Worksheet::SHEETSTATE_HIDDEN,
            0x02 => Worksheet::SHEETSTATE_VERYHIDDEN,
            default => Worksheet::SHEETSTATE_VISIBLE,
        };

        
        $sheetType = ord($recordData[5]);

        
        $rec_name = null;
        if ($this->version == self::XLS_BIFF8) {
            $string = self::readUnicodeStringShort(substr($recordData, 6));
            $rec_name = $string['value'];
        } elseif ($this->version == self::XLS_BIFF7) {
            $string = $this->readByteStringShort(substr($recordData, 6));
            $rec_name = $string['value'];
        }

        $this->sheets[] = [
            'name' => $rec_name,
            'offset' => $rec_offset,
            'sheetState' => $sheetState,
            'sheetType' => $sheetType,
        ];
    }

    
    protected function readExternalBook(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $offset = 0;

        
        if (strlen($recordData) > 4) {
            
            
            $nm = self::getUInt2d($recordData, 0);
            $offset += 2;

            
            $encodedUrlString = self::readUnicodeStringLong(substr($recordData, 2));
            $offset += $encodedUrlString['size'];

            
            $externalSheetNames = [];
            for ($i = 0; $i < $nm; ++$i) {
                $externalSheetNameString = self::readUnicodeStringLong(substr($recordData, $offset));
                $externalSheetNames[] = $externalSheetNameString['value'];
                $offset += $externalSheetNameString['size'];
            }

            
            $this->externalBooks[] = [
                'type' => 'external',
                'encodedUrl' => $encodedUrlString['value'],
                'externalSheetNames' => $externalSheetNames,
            ];
        } elseif (substr($recordData, 2, 2) == pack('CC', 0x01, 0x04)) {
            
            
            
            $this->externalBooks[] = [
                'type' => 'internal',
            ];
        } elseif (substr($recordData, 0, 4) == pack('vCC', 0x0001, 0x01, 0x3A)) {
            
            
            $this->externalBooks[] = [
                'type' => 'addInFunction',
            ];
        } elseif (substr($recordData, 0, 2) == pack('v', 0x0000)) {
            
            
            
            $this->externalBooks[] = [
                'type' => 'DDEorOLE',
            ];
        }
    }

    
    protected function readExternName(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        if ($this->version == self::XLS_BIFF8) {
            
            

            

            

            
            $nameString = self::readUnicodeStringShort(substr($recordData, 6));

            
            $offset = 6 + $nameString['size'];
            $formula = $this->getFormulaFromStructure(substr($recordData, $offset));

            $this->externalNames[] = [
                'name' => $nameString['value'],
                'formula' => $formula,
            ];
        }
    }

    
    protected function readExternSheet(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        if ($this->version == self::XLS_BIFF8) {
            
            $nm = self::getUInt2d($recordData, 0);
            for ($i = 0; $i < $nm; ++$i) {
                $this->ref[] = [
                    
                    'externalBookIndex' => self::getUInt2d($recordData, 2 + 6 * $i),
                    
                    'firstSheetIndex' => self::getUInt2d($recordData, 4 + 6 * $i),
                    
                    'lastSheetIndex' => self::getUInt2d($recordData, 6 + 6 * $i),
                ];
            }
        }
    }

    
    protected function readDefinedName(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8) {
            

            
            $opts = self::getUInt2d($recordData, 0);

            
            $isBuiltInName = (0x0020 & $opts) >> 5;

            

            
            $nlen = ord($recordData[3]);

            
            
            $flen = self::getUInt2d($recordData, 4);

            
            $scope = self::getUInt2d($recordData, 8);

            
            $string = self::readUnicodeString(substr($recordData, 14), $nlen);

            
            $offset = 14 + $string['size'];
            $formulaStructure = pack('v', $flen) . substr($recordData, $offset);

            try {
                $formula = $this->getFormulaFromStructure($formulaStructure);
            } catch (PhpSpreadsheetException) {
                $formula = '';
                $isBuiltInName = 0;
            }

            $this->definedname[] = [
                'isBuiltInName' => $isBuiltInName,
                'name' => $string['value'],
                'formula' => $formula,
                'scope' => $scope,
            ];
        }
    }

    
    protected function readMsoDrawingGroup(): void
    {
        

        
        $splicedRecordData = $this->getSplicedRecordData();
        $recordData = $splicedRecordData['recordData'];

        $this->drawingGroupData .= $recordData;
    }

    
    protected function readSst(): void
    {
        
        $pos = 0;

        
        $limitposSST = 0;

        
        $splicedRecordData = $this->getSplicedRecordData();

        $recordData = $splicedRecordData['recordData'];
        $spliceOffsets = $splicedRecordData['spliceOffsets'];

        
        $pos += 4;

        
        $nm = self::getInt4d($recordData, 4);
        $pos += 4;

        
        foreach ($spliceOffsets as $spliceOffset) {
            
            
            if ($pos <= $spliceOffset) {
                $limitposSST = $spliceOffset;
            }
        }

        
        for ($i = 0; $i < $nm && $pos < $limitposSST; ++$i) {
            
            $numChars = self::getUInt2d($recordData, $pos);
            $pos += 2;

            
            $optionFlags = ord($recordData[$pos]);
            ++$pos;

            
            $isCompressed = (($optionFlags & 0x01) == 0);

            
            $hasAsian = (($optionFlags & 0x04) != 0);

            
            $hasRichText = (($optionFlags & 0x08) != 0);

            $formattingRuns = 0;
            if ($hasRichText) {
                
                $formattingRuns = self::getUInt2d($recordData, $pos);
                $pos += 2;
            }

            $extendedRunLength = 0;
            if ($hasAsian) {
                
                $extendedRunLength = self::getInt4d($recordData, $pos);
                $pos += 4;
            }

            
            $len = ($isCompressed) ? $numChars : $numChars * 2;

            
            $limitpos = null;
            foreach ($spliceOffsets as $spliceOffset) {
                
                
                if ($pos <= $spliceOffset) {
                    $limitpos = $spliceOffset;

                    break;
                }
            }

            if ($pos + $len <= $limitpos) {
                

                $retstr = substr($recordData, $pos, $len);
                $pos += $len;
            } else {
                

                
                $retstr = substr($recordData, $pos, $limitpos - $pos);

                $bytesRead = $limitpos - $pos;

                
                $charsLeft = $numChars - (($isCompressed) ? $bytesRead : ($bytesRead / 2));

                $pos = $limitpos;

                
                while ($charsLeft > 0) {
                    
                    foreach ($spliceOffsets as $spliceOffset) {
                        if ($pos < $spliceOffset) {
                            $limitpos = $spliceOffset;

                            break;
                        }
                    }

                    
                    
                    $option = ord($recordData[$pos]);
                    ++$pos;

                    if ($isCompressed && ($option == 0)) {
                        
                        
                        $len = min($charsLeft, $limitpos - $pos);
                        $retstr .= substr($recordData, $pos, $len);
                        $charsLeft -= $len;
                        $isCompressed = true;
                    } elseif (!$isCompressed && ($option != 0)) {
                        
                        
                        $len = min($charsLeft * 2, $limitpos - $pos);
                        $retstr .= substr($recordData, $pos, $len);
                        $charsLeft -= $len / 2;
                        $isCompressed = false;
                    } elseif (!$isCompressed && ($option == 0)) {
                        
                        
                        $len = min($charsLeft, $limitpos - $pos);
                        for ($j = 0; $j < $len; ++$j) {
                            $retstr .= $recordData[$pos + $j]
                                . chr(0);
                        }
                        $charsLeft -= $len;
                        $isCompressed = false;
                    } else {
                        
                        
                        $newstr = '';
                        $jMax = strlen($retstr);
                        for ($j = 0; $j < $jMax; ++$j) {
                            $newstr .= $retstr[$j] . chr(0);
                        }
                        $retstr = $newstr;
                        $len = min($charsLeft * 2, $limitpos - $pos);
                        $retstr .= substr($recordData, $pos, $len);
                        $charsLeft -= $len / 2;
                        $isCompressed = false;
                    }

                    $pos += $len;
                }
            }

            
            $retstr = self::encodeUTF16($retstr, $isCompressed);

            
            $fmtRuns = [];
            if ($hasRichText) {
                
                for ($j = 0; $j < $formattingRuns; ++$j) {
                    
                    $charPos = self::getUInt2d($recordData, $pos + $j * 4);

                    
                    $fontIndex = self::getUInt2d($recordData, $pos + 2 + $j * 4);

                    $fmtRuns[] = [
                        'charPos' => $charPos,
                        'fontIndex' => $fontIndex,
                    ];
                }
                $pos += 4 * $formattingRuns;
            }

            
            if ($hasAsian) {
                
                $pos += $extendedRunLength;
            }

            
            $this->sst[] = [
                'value' => $retstr,
                'fmtRuns' => $fmtRuns,
            ];
        }

        
    }

    
    protected function readPrintGridlines(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
            
            $printGridlines = (bool) self::getUInt2d($recordData, 0);
            $this->phpSheet->setPrintGridlines($printGridlines);
        }
    }

    
    protected function readDefaultRowHeight(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        
        $height = self::getUInt2d($recordData, 2);
        $this->phpSheet->getDefaultRowDimension()->setRowHeight($height / 20);
    }

    
    protected function readSheetPr(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        

        
        $isSummaryBelow = (0x0040 & self::getUInt2d($recordData, 0)) >> 6;
        $this->phpSheet->setShowSummaryBelow((bool) $isSummaryBelow);

        
        $isSummaryRight = (0x0080 & self::getUInt2d($recordData, 0)) >> 7;
        $this->phpSheet->setShowSummaryRight((bool) $isSummaryRight);

        
        
        $this->isFitToPages = (bool) ((0x0100 & self::getUInt2d($recordData, 0)) >> 8);
    }

    
    protected function readHorizontalPageBreaks(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
            
            $nm = self::getUInt2d($recordData, 0);

            
            for ($i = 0; $i < $nm; ++$i) {
                $r = self::getUInt2d($recordData, 2 + 6 * $i);
                $cf = self::getUInt2d($recordData, 2 + 6 * $i + 2);
                

                
                $this->phpSheet->setBreak([$cf + 1, $r], Worksheet::BREAK_ROW);
            }
        }
    }

    
    protected function readVerticalPageBreaks(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
            
            $nm = self::getUInt2d($recordData, 0);

            
            for ($i = 0; $i < $nm; ++$i) {
                $c = self::getUInt2d($recordData, 2 + 6 * $i);
                $rf = self::getUInt2d($recordData, 2 + 6 * $i + 2);
                

                
                $this->phpSheet->setBreak([$c + 1, ($rf > 0) ? $rf : 1], Worksheet::BREAK_COLUMN);
            }
        }
    }

    
    protected function readHeader(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            
            if ($recordData) {
                if ($this->version == self::XLS_BIFF8) {
                    $string = self::readUnicodeStringLong($recordData);
                } else {
                    $string = $this->readByteStringShort($recordData);
                }

                $this->phpSheet->getHeaderFooter()->setOddHeader($string['value']);
                $this->phpSheet->getHeaderFooter()->setEvenHeader($string['value']);
            }
        }
    }

    
    protected function readFooter(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            
            if ($recordData) {
                if ($this->version == self::XLS_BIFF8) {
                    $string = self::readUnicodeStringLong($recordData);
                } else {
                    $string = $this->readByteStringShort($recordData);
                }
                $this->phpSheet->getHeaderFooter()->setOddFooter($string['value']);
                $this->phpSheet->getHeaderFooter()->setEvenFooter($string['value']);
            }
        }
    }

    
    protected function readHcenter(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $isHorizontalCentered = (bool) self::getUInt2d($recordData, 0);

            $this->phpSheet->getPageSetup()->setHorizontalCentered($isHorizontalCentered);
        }
    }

    
    protected function readVcenter(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $isVerticalCentered = (bool) self::getUInt2d($recordData, 0);

            $this->phpSheet->getPageSetup()->setVerticalCentered($isVerticalCentered);
        }
    }

    
    protected function readLeftMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $this->phpSheet->getPageMargins()->setLeft(self::extractNumber($recordData));
        }
    }

    
    protected function readRightMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $this->phpSheet->getPageMargins()->setRight(self::extractNumber($recordData));
        }
    }

    
    protected function readTopMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $this->phpSheet->getPageMargins()->setTop(self::extractNumber($recordData));
        }
    }

    
    protected function readBottomMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $this->phpSheet->getPageMargins()->setBottom(self::extractNumber($recordData));
        }
    }

    
    protected function readPageSetup(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $paperSize = self::getUInt2d($recordData, 0);

            
            $scale = self::getUInt2d($recordData, 2);

            
            $fitToWidth = self::getUInt2d($recordData, 6);

            
            $fitToHeight = self::getUInt2d($recordData, 8);

            

            
            $isOverThenDown = (0x0001 & self::getUInt2d($recordData, 10));

            
            $isPortrait = (0x0002 & self::getUInt2d($recordData, 10)) >> 1;

            
            
            $isNotInit = (0x0004 & self::getUInt2d($recordData, 10)) >> 2;

            if (!$isNotInit) {
                $this->phpSheet->getPageSetup()->setPaperSize($paperSize);
                $this->phpSheet->getPageSetup()->setPageOrder(((bool) $isOverThenDown) ? PageSetup::PAGEORDER_OVER_THEN_DOWN : PageSetup::PAGEORDER_DOWN_THEN_OVER);
                $this->phpSheet->getPageSetup()->setOrientation(((bool) $isPortrait) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE);

                $this->phpSheet->getPageSetup()->setScale($scale, false);
                $this->phpSheet->getPageSetup()->setFitToPage((bool) $this->isFitToPages);
                $this->phpSheet->getPageSetup()->setFitToWidth($fitToWidth, false);
                $this->phpSheet->getPageSetup()->setFitToHeight($fitToHeight, false);
            }

            
            $marginHeader = self::extractNumber(substr($recordData, 16, 8));
            $this->phpSheet->getPageMargins()->setHeader($marginHeader);

            
            $marginFooter = self::extractNumber(substr($recordData, 24, 8));
            $this->phpSheet->getPageMargins()->setFooter($marginFooter);
        }
    }

    
    protected function readProtect(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        

        
        $bool = (0x01 & self::getUInt2d($recordData, 0)) >> 0;
        $this->phpSheet->getProtection()->setSheet((bool) $bool);
    }

    
    protected function readScenProtect(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        

        
        $bool = (0x01 & self::getUInt2d($recordData, 0)) >> 0;

        $this->phpSheet->getProtection()->setScenarios((bool) $bool);
    }

    
    protected function readObjectProtect(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        

        
        $bool = (0x01 & self::getUInt2d($recordData, 0)) >> 0;

        $this->phpSheet->getProtection()->setObjects((bool) $bool);
    }

    
    protected function readPassword(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $password = strtoupper(dechex(self::getUInt2d($recordData, 0))); 
            $this->phpSheet->getProtection()->setPassword($password, true);
        }
    }

    
    protected function readDefColWidth(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $width = self::getUInt2d($recordData, 0);
        if ($width != 8) {
            $this->phpSheet->getDefaultColumnDimension()->setWidth($width);
        }
    }

    
    protected function readColInfo(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $firstColumnIndex = self::getUInt2d($recordData, 0);

            
            $lastColumnIndex = self::getUInt2d($recordData, 2);

            
            $width = self::getUInt2d($recordData, 4);

            
            $xfIndex = self::getUInt2d($recordData, 6);

            
            
            $isHidden = (0x0001 & self::getUInt2d($recordData, 8)) >> 0;

            
            $level = (0x0700 & self::getUInt2d($recordData, 8)) >> 8;

            
            $isCollapsed = (bool) ((0x1000 & self::getUInt2d($recordData, 8)) >> 12);

            

            for ($i = $firstColumnIndex + 1; $i <= $lastColumnIndex + 1; ++$i) {
                if ($lastColumnIndex == 255 || $lastColumnIndex == 256) {
                    $this->phpSheet->getDefaultColumnDimension()->setWidth($width / 256);

                    break;
                }
                $this->phpSheet->getColumnDimensionByColumn($i)->setWidth($width / 256);
                $this->phpSheet->getColumnDimensionByColumn($i)->setVisible(!$isHidden);
                $this->phpSheet->getColumnDimensionByColumn($i)->setOutlineLevel($level);
                $this->phpSheet->getColumnDimensionByColumn($i)->setCollapsed($isCollapsed);
                if (isset($this->mapCellXfIndex[$xfIndex])) {
                    $this->phpSheet->getColumnDimensionByColumn($i)->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                }
            }
        }
    }

    
    protected function readRow(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $r = self::getUInt2d($recordData, 0);

            

            

            

            
            $height = (0x7FFF & self::getUInt2d($recordData, 6)) >> 0;

            
            $useDefaultHeight = (0x8000 & self::getUInt2d($recordData, 6)) >> 15;

            if (!$useDefaultHeight) {
                $this->phpSheet->getRowDimension($r + 1)->setRowHeight($height / 20);
            }

            

            

            

            
            $level = (0x00000007 & self::getInt4d($recordData, 12)) >> 0;
            $this->phpSheet->getRowDimension($r + 1)->setOutlineLevel($level);

            
            $isCollapsed = (bool) ((0x00000010 & self::getInt4d($recordData, 12)) >> 4);
            $this->phpSheet->getRowDimension($r + 1)->setCollapsed($isCollapsed);

            
            $isHidden = (0x00000020 & self::getInt4d($recordData, 12)) >> 5;
            $this->phpSheet->getRowDimension($r + 1)->setVisible(!$isHidden);

            
            $hasExplicitFormat = (0x00000080 & self::getInt4d($recordData, 12)) >> 7;

            
            $xfIndex = (0x0FFF0000 & self::getInt4d($recordData, 12)) >> 16;

            if ($hasExplicitFormat && isset($this->mapCellXfIndex[$xfIndex])) {
                $this->phpSheet->getRowDimension($r + 1)->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

    
    protected function readRk(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            
            $xfIndex = self::getUInt2d($recordData, 4);

            
            $rknum = self::getInt4d($recordData, 6);
            $numValue = self::getIEEE754($rknum);

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                
                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }

            
            $cell->setValueExplicit($numValue, DataType::TYPE_NUMERIC);
        }
    }

    
    protected function readLabelSst(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        $cell = null;
        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            
            $xfIndex = self::getUInt2d($recordData, 4);

            
            $index = self::getInt4d($recordData, 6);

            
            if (($fmtRuns = $this->sst[$index]['fmtRuns']) && !$this->readDataOnly) {
                
                $richText = new RichText();
                $charPos = 0;
                $sstCount = count($this->sst[$index]['fmtRuns']);
                for ($i = 0; $i <= $sstCount; ++$i) {
                    if (isset($fmtRuns[$i])) {
                        $text = StringHelper::substring($this->sst[$index]['value'], $charPos, $fmtRuns[$i]['charPos'] - $charPos);
                        $charPos = $fmtRuns[$i]['charPos'];
                    } else {
                        $text = StringHelper::substring($this->sst[$index]['value'], $charPos, StringHelper::countCharacters($this->sst[$index]['value']));
                    }

                    if (StringHelper::countCharacters($text) > 0) {
                        if ($i == 0) { 
                            $richText->createText($text);
                        } else {
                            $textRun = $richText->createTextRun($text);
                            if (isset($fmtRuns[$i - 1])) {
                                if ($fmtRuns[$i - 1]['fontIndex'] < 4) {
                                    $fontIndex = $fmtRuns[$i - 1]['fontIndex'];
                                } else {
                                    
                                    
                                    $fontIndex = $fmtRuns[$i - 1]['fontIndex'] - 1;
                                }
                                if (array_key_exists($fontIndex, $this->objFonts) === false) {
                                    $fontIndex = count($this->objFonts) - 1;
                                }
                                $textRun->setFont(clone $this->objFonts[$fontIndex]);
                            }
                        }
                    }
                }
                if ($this->readEmptyCells || trim($richText->getPlainText()) !== '') {
                    $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                    $cell->setValueExplicit($richText, DataType::TYPE_STRING);
                }
            } else {
                if ($this->readEmptyCells || trim($this->sst[$index]['value']) !== '') {
                    $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                    $cell->setValueExplicit($this->sst[$index]['value'], DataType::TYPE_STRING);
                }
            }

            if (!$this->readDataOnly && $cell !== null && isset($this->mapCellXfIndex[$xfIndex])) {
                
                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

    
    protected function readMulRk(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $colFirst = self::getUInt2d($recordData, 2);

        
        $colLast = self::getUInt2d($recordData, $length - 2);
        $columns = $colLast - $colFirst + 1;

        
        $offset = 4;

        for ($i = 1; $i <= $columns; ++$i) {
            $columnString = Coordinate::stringFromColumnIndex($colFirst + $i);

            
            if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                
                $xfIndex = self::getUInt2d($recordData, $offset);

                
                $numValue = self::getIEEE754(self::getInt4d($recordData, $offset + 2));
                $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                    
                    $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                }

                
                $cell->setValueExplicit($numValue, DataType::TYPE_NUMERIC);
            }

            $offset += 6;
        }
    }

    
    protected function readNumber(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            
            $xfIndex = self::getUInt2d($recordData, 4);

            $numValue = self::extractNumber(substr($recordData, 6, 8));

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                
                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }

            
            $cell->setValueExplicit($numValue, DataType::TYPE_NUMERIC);
        }
    }

    
    protected function readFormula(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        
        $formulaStructure = substr($recordData, 20);

        
        $options = self::getUInt2d($recordData, 14);

        
        
        
        $isPartOfSharedFormula = (bool) (0x0008 & $options);

        
        
        
        
        $isPartOfSharedFormula = $isPartOfSharedFormula && ord($formulaStructure[2]) == 0x01;

        if ($isPartOfSharedFormula) {
            
            
            $baseRow = self::getUInt2d($formulaStructure, 3);
            $baseCol = self::getUInt2d($formulaStructure, 5);
            $this->baseCell = Coordinate::stringFromColumnIndex($baseCol + 1) . ($baseRow + 1);
        }

        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            if ($isPartOfSharedFormula) {
                
                $this->sharedFormulaParts[$columnString . ($row + 1)] = $this->baseCell;
            }

            

            
            $xfIndex = self::getUInt2d($recordData, 4);

            
            if ((ord($recordData[6]) == 0) && (ord($recordData[12]) == 255) && (ord($recordData[13]) == 255)) {
                
                $dataType = DataType::TYPE_STRING;

                
                $code = self::getUInt2d($this->data, $this->pos);
                if ($code == self::XLS_TYPE_SHAREDFMLA) {
                    $this->readSharedFmla();
                }

                
                $value = $this->readString();
            } elseif (
                (ord($recordData[6]) == 1)
                && (ord($recordData[12]) == 255)
                && (ord($recordData[13]) == 255)
            ) {
                
                $dataType = DataType::TYPE_BOOL;
                $value = (bool) ord($recordData[8]);
            } elseif (
                (ord($recordData[6]) == 2)
                && (ord($recordData[12]) == 255)
                && (ord($recordData[13]) == 255)
            ) {
                
                $dataType = DataType::TYPE_ERROR;
                $value = Xls\ErrorCode::lookup(ord($recordData[8]));
            } elseif (
                (ord($recordData[6]) == 3)
                && (ord($recordData[12]) == 255)
                && (ord($recordData[13]) == 255)
            ) {
                
                $dataType = DataType::TYPE_NULL;
                $value = '';
            } else {
                
                $dataType = DataType::TYPE_NUMERIC;
                $value = self::extractNumber(substr($recordData, 6, 8));
            }

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                
                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }

            
            if (!$isPartOfSharedFormula) {
                
                
                try {
                    if ($this->version != self::XLS_BIFF8) {
                        throw new Exception('Not BIFF8. Can only read BIFF8 formulas');
                    }
                    $formula = $this->getFormulaFromStructure($formulaStructure); 
                    $cell->setValueExplicit('=' . $formula, DataType::TYPE_FORMULA);
                } catch (PhpSpreadsheetException) {
                    $cell->setValueExplicit($value, $dataType);
                }
            } else {
                if ($this->version == self::XLS_BIFF8) {
                    
                } else {
                    $cell->setValueExplicit($value, $dataType);
                }
            }

            
            $cell->setCalculatedValue($value, $dataType === DataType::TYPE_NUMERIC);
        }
    }

    
    protected function readSharedFmla(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        
        

        

        
        

        
        $formula = substr($recordData, 8);

        
        $this->sharedFormulas[$this->baseCell] = $formula;
    }

    
    protected function readString(): string
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8) {
            $string = self::readUnicodeStringLong($recordData);
            $value = $string['value'];
        } else {
            $string = $this->readByteStringLong($recordData);
            $value = $string['value'];
        }

        return $value;
    }

    
    protected function readBoolErr(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            
            $xfIndex = self::getUInt2d($recordData, 4);

            
            $boolErr = ord($recordData[6]);

            
            $isError = ord($recordData[7]);

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            switch ($isError) {
                case 0: 
                    $value = (bool) $boolErr;

                    
                    $cell->setValueExplicit($value, DataType::TYPE_BOOL);

                    break;
                case 1: 
                    $value = Xls\ErrorCode::lookup($boolErr);

                    
                    $cell->setValueExplicit($value, DataType::TYPE_ERROR);

                    break;
            }

            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                
                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

    
    protected function readMulBlank(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $fc = self::getUInt2d($recordData, 2);

        
        
        if (!$this->readDataOnly && $this->readEmptyCells) {
            for ($i = 0; $i < $length / 2 - 3; ++$i) {
                $columnString = Coordinate::stringFromColumnIndex($fc + $i + 1);

                
                if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                    $xfIndex = self::getUInt2d($recordData, 4 + 2 * $i);
                    if (isset($this->mapCellXfIndex[$xfIndex])) {
                        $this->phpSheet->getCell($columnString . ($row + 1))->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                    }
                }
            }
        }

        
    }

    
    protected function readLabel(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            
            $xfIndex = self::getUInt2d($recordData, 4);

            
            
            if ($this->version == self::XLS_BIFF8) {
                $string = self::readUnicodeStringLong(substr($recordData, 6));
                $value = $string['value'];
            } else {
                $string = $this->readByteStringLong(substr($recordData, 6));
                $value = $string['value'];
            }
            if ($this->readEmptyCells || trim($value) !== '') {
                $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                $cell->setValueExplicit($value, DataType::TYPE_STRING);

                if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                    
                    $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                }
            }
        }
    }

    
    protected function readBlank(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $row = self::getUInt2d($recordData, 0);

        
        $col = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($col + 1);

        
        if ($this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            
            $xfIndex = self::getUInt2d($recordData, 4);

            
            if (!$this->readDataOnly && $this->readEmptyCells && isset($this->mapCellXfIndex[$xfIndex])) {
                $this->phpSheet->getCell($columnString . ($row + 1))->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

    
    protected function readMsoDrawing(): void
    {
        

        
        $splicedRecordData = $this->getSplicedRecordData();
        $recordData = $splicedRecordData['recordData'];

        $this->drawingData .= $recordData;
    }

    
    protected function readObj(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly || $this->version != self::XLS_BIFF8) {
            return;
        }

        
        
        
        
        
        
        

        
        $ftCmoType = self::getUInt2d($recordData, 0);
        $cbCmoSize = self::getUInt2d($recordData, 2);
        $otObjType = self::getUInt2d($recordData, 4);
        $idObjID = self::getUInt2d($recordData, 6);
        $grbitOpts = self::getUInt2d($recordData, 6);

        $this->objs[] = [
            'ftCmoType' => $ftCmoType,
            'cbCmoSize' => $cbCmoSize,
            'otObjType' => $otObjType,
            'idObjID' => $idObjID,
            'grbitOpts' => $grbitOpts,
        ];
        $this->textObjRef = $idObjID;
    }

    
    protected function readWindow2(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $options = self::getUInt2d($recordData, 0);

        
        

        
        
        $zoomscaleInPageBreakPreview = 0;
        $zoomscaleInNormalView = 0;
        if ($this->version === self::XLS_BIFF8) {
            
            
            
            
            if (!isset($recordData[10])) {
                $zoomscaleInPageBreakPreview = 0;
            } else {
                $zoomscaleInPageBreakPreview = self::getUInt2d($recordData, 10);
            }

            if ($zoomscaleInPageBreakPreview === 0) {
                $zoomscaleInPageBreakPreview = 60;
            }

            if (!isset($recordData[12])) {
                $zoomscaleInNormalView = 0;
            } else {
                $zoomscaleInNormalView = self::getUInt2d($recordData, 12);
            }

            if ($zoomscaleInNormalView === 0) {
                $zoomscaleInNormalView = 100;
            }
        }

        
        $showGridlines = (bool) ((0x0002 & $options) >> 1);
        $this->phpSheet->setShowGridlines($showGridlines);

        
        $showRowColHeaders = (bool) ((0x0004 & $options) >> 2);
        $this->phpSheet->setShowRowColHeaders($showRowColHeaders);

        
        $this->frozen = (bool) ((0x0008 & $options) >> 3);

        
        $this->phpSheet->setRightToLeft((bool) ((0x0040 & $options) >> 6));

        
        $isActive = (bool) ((0x0400 & $options) >> 10);
        if ($isActive) {
            $this->spreadsheet->setActiveSheetIndex($this->spreadsheet->getIndex($this->phpSheet));
            $this->activeSheetSet = true;
        }

        
        $isPageBreakPreview = (bool) ((0x0800 & $options) >> 11);

        

        if ($this->phpSheet->getSheetView()->getView() !== SheetView::SHEETVIEW_PAGE_LAYOUT) {
            
            $view = $isPageBreakPreview ? SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW : SheetView::SHEETVIEW_NORMAL;
            $this->phpSheet->getSheetView()->setView($view);
            if ($this->version === self::XLS_BIFF8) {
                $zoomScale = $isPageBreakPreview ? $zoomscaleInPageBreakPreview : $zoomscaleInNormalView;
                $this->phpSheet->getSheetView()->setZoomScale($zoomScale);
                $this->phpSheet->getSheetView()->setZoomScaleNormal($zoomscaleInNormalView);
            }
        }
    }

    
    protected function readPageLayoutView(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        
        
        
        
        
        
        

        
        $wScalePLV = self::getUInt2d($recordData, 12);
        
        $grbit = self::getUInt2d($recordData, 14);

        
        $fPageLayoutView = $grbit & 0x01;
        
        

        if ($fPageLayoutView === 1) {
            $this->phpSheet->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_LAYOUT);
            $this->phpSheet->getSheetView()->setZoomScale($wScalePLV); 
        }
        
    }

    
    protected function readScl(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $numerator = self::getUInt2d($recordData, 0);

        
        $denumerator = self::getUInt2d($recordData, 2);

        
        $this->phpSheet->getSheetView()->setZoomScale($numerator * 100 / $denumerator);
    }

    
    protected function readPane(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            $px = self::getUInt2d($recordData, 0);

            
            $py = self::getUInt2d($recordData, 2);

            
            $rwTop = self::getUInt2d($recordData, 4);

            
            $colLeft = self::getUInt2d($recordData, 6);

            if ($this->frozen) {
                
                $cell = Coordinate::stringFromColumnIndex($px + 1) . ($py + 1);
                $topLeftCell = Coordinate::stringFromColumnIndex($colLeft + 1) . ($rwTop + 1);
                $this->phpSheet->freezePane($cell, $topLeftCell);
            }
            
        }
    }

    
    protected function readSelection(): string
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);
        $selectedCells = '';

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            

            
            

            
            

            
            
            

            
            $data = substr($recordData, 7);
            $cellRangeAddressList = Xls\Biff5::readBIFF5CellRangeAddressList($data); 

            $selectedCells = $cellRangeAddressList['cellRangeAddresses'][0];

            
            if (preg_match('/^([A-Z]+1\:[A-Z]+)16384$/', $selectedCells)) {
                $selectedCells = (string) preg_replace('/^([A-Z]+1\:[A-Z]+)16384$/', '${1}1048576', $selectedCells);
            }

            
            if (preg_match('/^([A-Z]+1\:[A-Z]+)65536$/', $selectedCells)) {
                $selectedCells = (string) preg_replace('/^([A-Z]+1\:[A-Z]+)65536$/', '${1}1048576', $selectedCells);
            }

            
            if (preg_match('/^(A\d+\:)IV(\d+)$/', $selectedCells)) {
                $selectedCells = (string) preg_replace('/^(A\d+\:)IV(\d+)$/', '${1}XFD${2}', $selectedCells);
            }

            $this->phpSheet->setSelectedCells($selectedCells);
        }

        return $selectedCells;
    }

    private function includeCellRangeFiltered(string $cellRangeAddress): bool
    {
        $includeCellRange = false;
        $rangeBoundaries = Coordinate::getRangeBoundaries($cellRangeAddress);
        ++$rangeBoundaries[1][0];
        for ($row = $rangeBoundaries[0][1]; $row <= $rangeBoundaries[1][1]; ++$row) {
            for ($column = $rangeBoundaries[0][0]; $column != $rangeBoundaries[1][0]; ++$column) {
                if ($this->getReadFilter()->readCell($column, $row, $this->phpSheet->getTitle())) {
                    $includeCellRange = true;

                    break 2;
                }
            }
        }

        return $includeCellRange;
    }

    
    protected function readMergedCells(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
            $cellRangeAddressList = Xls\Biff8::readBIFF8CellRangeAddressList($recordData);
            foreach ($cellRangeAddressList['cellRangeAddresses'] as $cellRangeAddress) {
                if (
                    (str_contains($cellRangeAddress, ':'))
                    && ($this->includeCellRangeFiltered($cellRangeAddress))
                ) {
                    $this->phpSheet->mergeCells($cellRangeAddress, Worksheet::MERGE_CELL_CONTENT_HIDE);
                }
            }
        }
    }

    
    protected function readHyperLink(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            try {
                $cellRange = Xls\Biff8::readBIFF8CellRangeAddressFixed($recordData);
            } catch (PhpSpreadsheetException) {
                return;
            }

            

            

            
            
            $isFileLinkOrUrl = (0x00000001 & self::getUInt2d($recordData, 28)) >> 0;

            
            

            
            $hasDesc = (0x00000014 & self::getUInt2d($recordData, 28)) >> 2;

            
            $hasText = (0x00000008 & self::getUInt2d($recordData, 28)) >> 3;

            
            $hasFrame = (0x00000080 & self::getUInt2d($recordData, 28)) >> 7;

            
            $isUNC = (0x00000100 & self::getUInt2d($recordData, 28)) >> 8;

            
            $offset = 32;

            if ($hasDesc) {
                
                $dl = self::getInt4d($recordData, 32);
                
                
                $offset += 4 + 2 * $dl;
            }
            if ($hasFrame) {
                $fl = self::getInt4d($recordData, $offset);
                $offset += 4 + 2 * $fl;
            }

            
            $hyperlinkType = null;

            if ($isUNC) {
                $hyperlinkType = 'UNC';
            } elseif (!$isFileLinkOrUrl) {
                $hyperlinkType = 'workbook';
            } elseif (ord($recordData[$offset]) == 0x03) {
                $hyperlinkType = 'local';
            } elseif (ord($recordData[$offset]) == 0xE0) {
                $hyperlinkType = 'URL';
            }

            switch ($hyperlinkType) {
                case 'URL':
                    
                    

                    
                    $offset += 16;
                    
                    $us = self::getInt4d($recordData, $offset);
                    $offset += 4;
                    
                    $url = self::encodeUTF16(substr($recordData, $offset, $us - 2), false);
                    $nullOffset = strpos($url, chr(0x00));
                    if ($nullOffset) {
                        $url = substr($url, 0, $nullOffset);
                    }
                    $url .= $hasText ? '
                    $offset += $us;

                    break;
                case 'local':
                    
                    
                    
                    

                    
                    $offset += 16;

                    
                    $upLevelCount = self::getUInt2d($recordData, $offset);
                    $offset += 2;

                    
                    $sl = self::getInt4d($recordData, $offset);
                    $offset += 4;

                    
                    $shortenedFilePath = substr($recordData, $offset, $sl);
                    $shortenedFilePath = self::encodeUTF16($shortenedFilePath, true);
                    $shortenedFilePath = substr($shortenedFilePath, 0, -1); 

                    $offset += $sl;

                    
                    $offset += 24;

                    
                    
                    $sz = self::getInt4d($recordData, $offset);
                    $offset += 4;

                    $extendedFilePath = '';
                    
                    if ($sz > 0) {
                        
                        $xl = self::getInt4d($recordData, $offset);
                        $offset += 4;

                        
                        $offset += 2;

                        
                        $extendedFilePath = substr($recordData, $offset, $xl);
                        $extendedFilePath = self::encodeUTF16($extendedFilePath, false);
                        $offset += $xl;
                    }

                    
                    $url = str_repeat('..\\', $upLevelCount);
                    $url .= ($sz > 0) ? $extendedFilePath : $shortenedFilePath; 
                    $url .= $hasText ? '

                    break;
                case 'UNC':
                    
                    
                    return;
                case 'workbook':
                    
                    
                    $url = 'sheet://';

                    break;
                default:
                    return;
            }

            if ($hasText) {
                
                $tl = self::getInt4d($recordData, $offset);
                $offset += 4;
                
                $text = self::encodeUTF16(substr($recordData, $offset, 2 * ($tl - 1)), false);
                $url .= $text;
            }

            
            foreach (Coordinate::extractAllCellReferencesInRange($cellRange) as $coordinate) {
                $this->phpSheet->getCell($coordinate)->getHyperLink()->setUrl($url);
            }
        }
    }

    
    protected function readDataValidations(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        

        
        $this->pos += 4 + $length;
    }

    
    protected function readDataValidation(): void
    {
        (new Xls\DataValidationHelper())->readDataValidation2($this);
    }

    
    protected function readSheetLayout(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            

            

            
            
            $sz = self::getInt4d($recordData, 12);

            switch ($sz) {
                case 0x14:
                    
                    $colorIndex = self::getUInt2d($recordData, 16);
                    $color = Xls\Color::map($colorIndex, $this->palette, $this->version);
                    $this->phpSheet->getTabColor()->setRGB($color['rgb']);

                    break;
                case 0x28:
                    
                    return;
            }
        }
    }

    
    protected function readSheetProtection(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        

        

        

        
        $isf = self::getUInt2d($recordData, 12);
        if ($isf != 2) {
            return;
        }

        

        

        
        
        $options = self::getUInt2d($recordData, 19);

        
        
        $bool = (0x0001 & $options) >> 0;
        $this->phpSheet->getProtection()->setObjects((bool) $bool);

        
        
        $bool = (0x0002 & $options) >> 1;
        $this->phpSheet->getProtection()->setScenarios((bool) $bool);

        
        $bool = (0x0004 & $options) >> 2;
        $this->phpSheet->getProtection()->setFormatCells(!$bool);

        
        $bool = (0x0008 & $options) >> 3;
        $this->phpSheet->getProtection()->setFormatColumns(!$bool);

        
        $bool = (0x0010 & $options) >> 4;
        $this->phpSheet->getProtection()->setFormatRows(!$bool);

        
        $bool = (0x0020 & $options) >> 5;
        $this->phpSheet->getProtection()->setInsertColumns(!$bool);

        
        $bool = (0x0040 & $options) >> 6;
        $this->phpSheet->getProtection()->setInsertRows(!$bool);

        
        $bool = (0x0080 & $options) >> 7;
        $this->phpSheet->getProtection()->setInsertHyperlinks(!$bool);

        
        $bool = (0x0100 & $options) >> 8;
        $this->phpSheet->getProtection()->setDeleteColumns(!$bool);

        
        $bool = (0x0200 & $options) >> 9;
        $this->phpSheet->getProtection()->setDeleteRows(!$bool);

        
        
        $bool = (0x0400 & $options) >> 10;
        $this->phpSheet->getProtection()->setSelectLockedCells((bool) $bool);

        
        $bool = (0x0800 & $options) >> 11;
        $this->phpSheet->getProtection()->setSort(!$bool);

        
        $bool = (0x1000 & $options) >> 12;
        $this->phpSheet->getProtection()->setAutoFilter(!$bool);

        
        $bool = (0x2000 & $options) >> 13;
        $this->phpSheet->getProtection()->setPivotTables(!$bool);

        
        
        $bool = (0x4000 & $options) >> 14;
        $this->phpSheet->getProtection()->setSelectUnlockedCells((bool) $bool);

        
    }

    
    protected function readRangeProtection(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        $this->pos += 4 + $length;

        
        $offset = 0;

        if (!$this->readDataOnly) {
            $offset += 12;

            
            $isf = self::getUInt2d($recordData, 12);
            if ($isf != 2) {
                
                return;
            }
            $offset += 2;

            $offset += 5;

            
            $cref = self::getUInt2d($recordData, 19);
            $offset += 2;

            $offset += 6;

            
            $cellRanges = [];
            for ($i = 0; $i < $cref; ++$i) {
                try {
                    $cellRange = Xls\Biff8::readBIFF8CellRangeAddressFixed(substr($recordData, 27 + 8 * $i, 8));
                } catch (PhpSpreadsheetException) {
                    return;
                }
                $cellRanges[] = $cellRange;
                $offset += 8;
            }

            
            
            $offset += 4;

            
            $wPassword = self::getInt4d($recordData, $offset);
            $offset += 4;

            
            if ($cellRanges) {
                $this->phpSheet->protectCells(implode(' ', $cellRanges), ($wPassword === 0) ? '' : strtoupper(dechex($wPassword)), true);
            }
        }
    }

    
    protected function readContinue(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

        
        
        if ($this->drawingData == '') {
            
            $this->pos += 4 + $length;

            return;
        }

        
        if ($length < 4) {
            
            $this->pos += 4 + $length;

            return;
        }

        
        
        
        
        
        
        $validSplitPoints = [0xF003, 0xF004, 0xF00D]; 

        $splitPoint = self::getUInt2d($recordData, 2);
        if (in_array($splitPoint, $validSplitPoints)) {
            
            $splicedRecordData = $this->getSplicedRecordData();
            $this->drawingData .= $splicedRecordData['recordData'];

            return;
        }

        
        $this->pos += 4 + $length;
    }

    
    private function getSplicedRecordData(): array
    {
        $data = '';
        $spliceOffsets = [];

        $i = 0;
        $spliceOffsets[0] = 0;

        do {
            ++$i;

            
            
            
            $length = self::getUInt2d($this->data, $this->pos + 2);
            $data .= $this->readRecordData($this->data, $this->pos + 4, $length);

            $spliceOffsets[$i] = $spliceOffsets[$i - 1] + $length;

            $this->pos += 4 + $length;
            $nextIdentifier = self::getUInt2d($this->data, $this->pos);
        } while ($nextIdentifier == self::XLS_TYPE_CONTINUE);

        return [
            'recordData' => $data,
            'spliceOffsets' => $spliceOffsets,
        ];
    }

    
    protected function getFormulaFromStructure(string $formulaStructure, string $baseCell = 'A1'): string
    {
        
        $sz = self::getUInt2d($formulaStructure, 0);

        
        $formulaData = substr($formulaStructure, 2, $sz);

        
        if (strlen($formulaStructure) > 2 + $sz) {
            $additionalData = substr($formulaStructure, 2 + $sz);
        } else {
            $additionalData = '';
        }

        return $this->getFormulaFromData($formulaData, $additionalData, $baseCell);
    }

    
    private function getFormulaFromData(string $formulaData, string $additionalData = '', string $baseCell = 'A1'): string
    {
        
        $tokens = [];

        while ($formulaData !== '' && $token = $this->getNextToken($formulaData, $baseCell)) {
            $tokens[] = $token;
            $formulaData = substr($formulaData, $token['size']);
        }

        $formulaString = $this->createFormulaFromTokens($tokens, $additionalData);

        return $formulaString;
    }

    
    private function createFormulaFromTokens(array $tokens, string $additionalData): string
    {
        
        if (empty($tokens)) {
            return '';
        }

        $formulaStrings = [];
        foreach ($tokens as $token) {
            
            $space0 = $space0 ?? ''; 
            $space1 = $space1 ?? ''; 
            $space2 = $space2 ?? ''; 
            $space3 = $space3 ?? ''; 
            $space4 = $space4 ?? ''; 
            $space5 = $space5 ?? ''; 

            switch ($token['name']) {
                case 'tAdd': 
                case 'tConcat': 
                case 'tDiv': 
                case 'tEQ': 
                case 'tGE': 
                case 'tGT': 
                case 'tIsect': 
                case 'tLE': 
                case 'tList': 
                case 'tLT': 
                case 'tMul': 
                case 'tNE': 
                case 'tPower': 
                case 'tRange': 
                case 'tSub': 
                    $op2 = array_pop($formulaStrings);
                    $op1 = array_pop($formulaStrings);
                    $formulaStrings[] = "$op1$space1$space0{$token['data']}$op2";
                    unset($space0, $space1);

                    break;
                case 'tUplus': 
                case 'tUminus': 
                    $op = array_pop($formulaStrings);
                    $formulaStrings[] = "$space1$space0{$token['data']}$op";
                    unset($space0, $space1);

                    break;
                case 'tPercent': 
                    $op = array_pop($formulaStrings);
                    $formulaStrings[] = "$op$space1$space0{$token['data']}";
                    unset($space0, $space1);

                    break;
                case 'tAttrVolatile': 
                case 'tAttrIf':
                case 'tAttrSkip':
                case 'tAttrChoose':
                    
                    
                    break;
                case 'tAttrSpace': 
                    
                    switch ($token['data']['spacetype']) {
                        case 'type0':
                            $space0 = str_repeat(' ', $token['data']['spacecount']);

                            break;
                        case 'type1':
                            $space1 = str_repeat("\n", $token['data']['spacecount']);

                            break;
                        case 'type2':
                            $space2 = str_repeat(' ', $token['data']['spacecount']);

                            break;
                        case 'type3':
                            $space3 = str_repeat("\n", $token['data']['spacecount']);

                            break;
                        case 'type4':
                            $space4 = str_repeat(' ', $token['data']['spacecount']);

                            break;
                        case 'type5':
                            $space5 = str_repeat("\n", $token['data']['spacecount']);

                            break;
                    }

                    break;
                case 'tAttrSum': 
                    $op = array_pop($formulaStrings);
                    $formulaStrings[] = "{$space1}{$space0}SUM($op)";
                    unset($space0, $space1);

                    break;
                case 'tFunc': 
                case 'tFuncV': 
                    if ($token['data']['function'] != '') {
                        
                        $ops = []; 
                        for ($i = 0; $i < $token['data']['args']; ++$i) {
                            $ops[] = array_pop($formulaStrings);
                        }
                        $ops = array_reverse($ops);
                        $formulaStrings[] = "$space1$space0{$token['data']['function']}(" . implode(',', $ops) . ')';
                        unset($space0, $space1);
                    } else {
                        
                        $ops = []; 
                        for ($i = 0; $i < $token['data']['args'] - 1; ++$i) {
                            $ops[] = array_pop($formulaStrings);
                        }
                        $ops = array_reverse($ops);
                        $function = array_pop($formulaStrings);
                        $formulaStrings[] = "$space1$space0$function(" . implode(',', $ops) . ')';
                        unset($space0, $space1);
                    }

                    break;
                case 'tParen': 
                    $expression = array_pop($formulaStrings);
                    $formulaStrings[] = "$space3$space2($expression$space5$space4)";
                    unset($space2, $space3, $space4, $space5);

                    break;
                case 'tArray': 
                    $constantArray = Xls\Biff8::readBIFF8ConstantArray($additionalData);
                    $formulaStrings[] = $space1 . $space0 . $constantArray['value'];
                    $additionalData = substr($additionalData, $constantArray['size']); 
                    unset($space0, $space1);

                    break;
                case 'tMemArea':
                    
                    $cellRangeAddressList = Xls\Biff8::readBIFF8CellRangeAddressList($additionalData);
                    $additionalData = substr($additionalData, $cellRangeAddressList['size']);
                    $formulaStrings[] = "$space1$space0{$token['data']}";
                    unset($space0, $space1);

                    break;
                case 'tArea': 
                case 'tBool': 
                case 'tErr': 
                case 'tInt': 
                case 'tMemErr':
                case 'tMemFunc':
                case 'tMissArg':
                case 'tName':
                case 'tNameX':
                case 'tNum': 
                case 'tRef': 
                case 'tRef3d': 
                case 'tArea3d': 
                case 'tRefN':
                case 'tAreaN':
                case 'tStr': 
                    $formulaStrings[] = "$space1$space0{$token['data']}";
                    unset($space0, $space1);

                    break;
            }
        }
        $formulaString = $formulaStrings[0];

        return $formulaString;
    }

    
    private function getNextToken(string $formulaData, string $baseCell = 'A1'): array
    {
        
        $id = ord($formulaData[0]); 
        $name = false; 

        switch ($id) {
            case 0x03:
                $name = 'tAdd';
                $size = 1;
                $data = '+';

                break;
            case 0x04:
                $name = 'tSub';
                $size = 1;
                $data = '-';

                break;
            case 0x05:
                $name = 'tMul';
                $size = 1;
                $data = '*';

                break;
            case 0x06:
                $name = 'tDiv';
                $size = 1;
                $data = '/';

                break;
            case 0x07:
                $name = 'tPower';
                $size = 1;
                $data = '^';

                break;
            case 0x08:
                $name = 'tConcat';
                $size = 1;
                $data = '&';

                break;
            case 0x09:
                $name = 'tLT';
                $size = 1;
                $data = '<';

                break;
            case 0x0A:
                $name = 'tLE';
                $size = 1;
                $data = '<=';

                break;
            case 0x0B:
                $name = 'tEQ';
                $size = 1;
                $data = '=';

                break;
            case 0x0C:
                $name = 'tGE';
                $size = 1;
                $data = '>=';

                break;
            case 0x0D:
                $name = 'tGT';
                $size = 1;
                $data = '>';

                break;
            case 0x0E:
                $name = 'tNE';
                $size = 1;
                $data = '<>';

                break;
            case 0x0F:
                $name = 'tIsect';
                $size = 1;
                $data = ' ';

                break;
            case 0x10:
                $name = 'tList';
                $size = 1;
                $data = ',';

                break;
            case 0x11:
                $name = 'tRange';
                $size = 1;
                $data = ':';

                break;
            case 0x12:
                $name = 'tUplus';
                $size = 1;
                $data = '+';

                break;
            case 0x13:
                $name = 'tUminus';
                $size = 1;
                $data = '-';

                break;
            case 0x14:
                $name = 'tPercent';
                $size = 1;
                $data = '%';

                break;
            case 0x15:    
                $name = 'tParen';
                $size = 1;
                $data = null;

                break;
            case 0x16:    
                $name = 'tMissArg';
                $size = 1;
                $data = '';

                break;
            case 0x17:    
                $name = 'tStr';
                
                $string = self::readUnicodeStringShort(substr($formulaData, 1));
                $size = 1 + $string['size'];
                $data = self::UTF8toExcelDoubleQuoted($string['value']);

                break;
            case 0x19:    
                
                switch (ord($formulaData[1])) {
                    case 0x01:
                        $name = 'tAttrVolatile';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x02:
                        $name = 'tAttrIf';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x04:
                        $name = 'tAttrChoose';
                        
                        $nc = self::getUInt2d($formulaData, 2);
                        
                        
                        $size = 2 * $nc + 6;
                        $data = null;

                        break;
                    case 0x08:
                        $name = 'tAttrSkip';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x10:
                        $name = 'tAttrSum';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x40:
                    case 0x41:
                        $name = 'tAttrSpace';
                        $size = 4;
                        
                        $spacetype = match (ord($formulaData[2])) {
                            0x00 => 'type0',
                            0x01 => 'type1',
                            0x02 => 'type2',
                            0x03 => 'type3',
                            0x04 => 'type4',
                            0x05 => 'type5',
                            default => throw new Exception('Unrecognized space type in tAttrSpace token'),
                        };
                        
                        $spacecount = ord($formulaData[3]);

                        $data = ['spacetype' => $spacetype, 'spacecount' => $spacecount];

                        break;
                    default:
                        throw new Exception('Unrecognized attribute flag in tAttr token');
                }

                break;
            case 0x1C:    
                
                $name = 'tErr';
                $size = 2;
                $data = Xls\ErrorCode::lookup(ord($formulaData[1]));

                break;
            case 0x1D:    
                
                $name = 'tBool';
                $size = 2;
                $data = ord($formulaData[1]) ? 'TRUE' : 'FALSE';

                break;
            case 0x1E:    
                
                $name = 'tInt';
                $size = 3;
                $data = self::getUInt2d($formulaData, 1);

                break;
            case 0x1F:    
                
                $name = 'tNum';
                $size = 9;
                $data = self::extractNumber(substr($formulaData, 1));
                $data = str_replace(',', '.', (string) $data); 

                break;
            case 0x20:    
            case 0x40:
            case 0x60:
                
                $name = 'tArray';
                $size = 8;
                $data = null;

                break;
            case 0x21:    
            case 0x41:
            case 0x61:
                $name = 'tFunc';
                $size = 3;
                
                $mapping = Xls\Mappings::TFUNC_MAPPINGS[self::getUInt2d($formulaData, 1)] ?? null;
                if ($mapping === null) {
                    throw new Exception('Unrecognized function in formula');
                }
                $data = ['function' => $mapping[0], 'args' => $mapping[1]];

                break;
            case 0x22:    
            case 0x42:
            case 0x62:
                $name = 'tFuncV';
                $size = 4;
                
                $args = ord($formulaData[1]);
                
                $index = self::getUInt2d($formulaData, 2);
                $function = Xls\Mappings::TFUNCV_MAPPINGS[$index] ?? null;
                if ($function === null) {
                    throw new Exception('Unrecognized function in formula');
                }
                $data = ['function' => $function, 'args' => $args];

                break;
            case 0x23:    
            case 0x43:
            case 0x63:
                $name = 'tName';
                $size = 5;
                
                $definedNameIndex = self::getUInt2d($formulaData, 1) - 1;
                
                $data = $this->definedname[$definedNameIndex]['name'] ?? '';

                break;
            case 0x24:    
            case 0x44:
            case 0x64:
                $name = 'tRef';
                $size = 5;
                $data = Xls\Biff8::readBIFF8CellAddress(substr($formulaData, 1, 4));

                break;
            case 0x25:    
            case 0x45:
            case 0x65:
                $name = 'tArea';
                $size = 9;
                $data = Xls\Biff8::readBIFF8CellRangeAddress(substr($formulaData, 1, 8));

                break;
            case 0x26:    
            case 0x46:
            case 0x66:
                $name = 'tMemArea';
                
                
                $subSize = self::getUInt2d($formulaData, 5);
                $size = 7 + $subSize;
                $data = $this->getFormulaFromData(substr($formulaData, 7, $subSize));

                break;
            case 0x27:    
            case 0x47:
            case 0x67:
                $name = 'tMemErr';
                
                
                $subSize = self::getUInt2d($formulaData, 5);
                $size = 7 + $subSize;
                $data = $this->getFormulaFromData(substr($formulaData, 7, $subSize));

                break;
            case 0x29:    
            case 0x49:
            case 0x69:
                $name = 'tMemFunc';
                
                $subSize = self::getUInt2d($formulaData, 1);
                $size = 3 + $subSize;
                $data = $this->getFormulaFromData(substr($formulaData, 3, $subSize));

                break;
            case 0x2C: 
            case 0x4C:
            case 0x6C:
                $name = 'tRefN';
                $size = 5;
                $data = Xls\Biff8::readBIFF8CellAddressB(substr($formulaData, 1, 4), $baseCell);

                break;
            case 0x2D:    
            case 0x4D:
            case 0x6D:
                $name = 'tAreaN';
                $size = 9;
                $data = Xls\Biff8::readBIFF8CellRangeAddressB(substr($formulaData, 1, 8), $baseCell);

                break;
            case 0x39:    
            case 0x59:
            case 0x79:
                $name = 'tNameX';
                $size = 7;
                
                
                $index = self::getUInt2d($formulaData, 3);
                
                $data = $this->externalNames[$index - 1]['name'] ?? '';

                
                break;
            case 0x3A:    
            case 0x5A:
            case 0x7A:
                $name = 'tRef3d';
                $size = 7;

                try {
                    
                    $sheetRange = $this->readSheetRangeByRefIndex(self::getUInt2d($formulaData, 1));
                    
                    $cellAddress = Xls\Biff8::readBIFF8CellAddress(substr($formulaData, 3, 4));

                    $data = "$sheetRange!$cellAddress";
                } catch (PhpSpreadsheetException) {
                    
                    $data = '
                }

                break;
            case 0x3B:    
            case 0x5B:
            case 0x7B:
                $name = 'tArea3d';
                $size = 11;

                try {
                    
                    $sheetRange = $this->readSheetRangeByRefIndex(self::getUInt2d($formulaData, 1));
                    
                    $cellRangeAddress = Xls\Biff8::readBIFF8CellRangeAddress(substr($formulaData, 3, 8));

                    $data = "$sheetRange!$cellRangeAddress";
                } catch (PhpSpreadsheetException) {
                    
                    $data = '
                }

                break;
                
            default:
                throw new Exception('Unrecognized token ' . sprintf('%02X', $id) . ' in formula');
        }

        return [
            'id' => $id,
            'name' => $name,
            'size' => $size,
            'data' => $data,
        ];
    }

    
    protected function readSheetRangeByRefIndex(int $index): string|false
    {
        if (isset($this->ref[$index])) {
            $type = $this->externalBooks[$this->ref[$index]['externalBookIndex']]['type'];

            switch ($type) {
                case 'internal':
                    
                    if ($this->ref[$index]['firstSheetIndex'] == 0xFFFF || $this->ref[$index]['lastSheetIndex'] == 0xFFFF) {
                        throw new Exception('Deleted sheet reference');
                    }

                    
                    $firstSheetName = $this->sheets[$this->ref[$index]['firstSheetIndex']]['name'];
                    $lastSheetName = $this->sheets[$this->ref[$index]['lastSheetIndex']]['name'];

                    if ($firstSheetName == $lastSheetName) {
                        
                        $sheetRange = $firstSheetName;
                    } else {
                        $sheetRange = "$firstSheetName:$lastSheetName";
                    }

                    
                    $sheetRange = str_replace("'", "''", $sheetRange);

                    
                    
                    
                    
                    if (preg_match("/[ !\"@
                        $sheetRange = "'$sheetRange'";
                    }

                    return $sheetRange;
                default:
                    
                    throw new Exception('Xls reader only supports internal sheets in formulas');
            }
        }

        return false;
    }

    
    protected function readByteStringShort(string $subData): array
    {
        
        $ln = ord($subData[0]);

        
        $value = $this->decodeCodepage(substr($subData, 1, $ln));

        return [
            'value' => $value,
            'size' => 1 + $ln, 
        ];
    }

    
    protected function readByteStringLong(string $subData): array
    {
        
        $ln = self::getUInt2d($subData, 0);

        
        $value = $this->decodeCodepage(substr($subData, 2));

        
        return [
            'value' => $value,
            'size' => 2 + $ln, 
        ];
    }

    protected function parseRichText(string $is): RichText
    {
        $value = new RichText();
        $value->createText($is);

        return $value;
    }

    
    public function getMapCellStyleXfIndex(): array
    {
        return $this->mapCellStyleXfIndex;
    }

    
    protected function readCFHeader(): array
    {
        return (new Xls\ConditionalFormatting())->readCFHeader2($this);
    }

    protected function readCFRule(array $cellRangeAddresses): void
    {
        (new Xls\ConditionalFormatting())->readCFRule2($cellRangeAddresses, $this);
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
