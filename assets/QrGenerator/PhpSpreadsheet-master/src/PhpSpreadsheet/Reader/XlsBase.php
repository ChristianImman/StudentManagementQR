<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Shared\CodePage;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\OLERead;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\Border;

class XlsBase extends BaseReader
{
    final protected const HIGH_ORDER_BIT = 0x80 << 24;
    final protected const FC000000 = 0xFC << 24;
    final protected const FE000000 = 0xFE << 24;

    
    final const XLS_BIFF8 = 0x0600;
    final const XLS_BIFF7 = 0x0500;
    final const XLS_WORKBOOKGLOBALS = 0x0005;
    final const XLS_WORKSHEET = 0x0010;

    
    final const XLS_TYPE_FORMULA = 0x0006;
    final const XLS_TYPE_EOF = 0x000A;
    final const XLS_TYPE_PROTECT = 0x0012;
    final const XLS_TYPE_OBJECTPROTECT = 0x0063;
    final const XLS_TYPE_SCENPROTECT = 0x00DD;
    final const XLS_TYPE_PASSWORD = 0x0013;
    final const XLS_TYPE_HEADER = 0x0014;
    final const XLS_TYPE_FOOTER = 0x0015;
    final const XLS_TYPE_EXTERNSHEET = 0x0017;
    final const XLS_TYPE_DEFINEDNAME = 0x0018;
    final const XLS_TYPE_VERTICALPAGEBREAKS = 0x001A;
    final const XLS_TYPE_HORIZONTALPAGEBREAKS = 0x001B;
    final const XLS_TYPE_NOTE = 0x001C;
    final const XLS_TYPE_SELECTION = 0x001D;
    final const XLS_TYPE_DATEMODE = 0x0022;
    final const XLS_TYPE_EXTERNNAME = 0x0023;
    final const XLS_TYPE_LEFTMARGIN = 0x0026;
    final const XLS_TYPE_RIGHTMARGIN = 0x0027;
    final const XLS_TYPE_TOPMARGIN = 0x0028;
    final const XLS_TYPE_BOTTOMMARGIN = 0x0029;
    final const XLS_TYPE_PRINTGRIDLINES = 0x002B;
    final const XLS_TYPE_FILEPASS = 0x002F;
    final const XLS_TYPE_FONT = 0x0031;
    final const XLS_TYPE_CONTINUE = 0x003C;
    final const XLS_TYPE_PANE = 0x0041;
    final const XLS_TYPE_CODEPAGE = 0x0042;
    final const XLS_TYPE_DEFCOLWIDTH = 0x0055;
    final const XLS_TYPE_OBJ = 0x005D;
    final const XLS_TYPE_COLINFO = 0x007D;
    final const XLS_TYPE_IMDATA = 0x007F;
    final const XLS_TYPE_SHEETPR = 0x0081;
    final const XLS_TYPE_HCENTER = 0x0083;
    final const XLS_TYPE_VCENTER = 0x0084;
    final const XLS_TYPE_SHEET = 0x0085;
    final const XLS_TYPE_PALETTE = 0x0092;
    final const XLS_TYPE_SCL = 0x00A0;
    final const XLS_TYPE_PAGESETUP = 0x00A1;
    final const XLS_TYPE_MULRK = 0x00BD;
    final const XLS_TYPE_MULBLANK = 0x00BE;
    final const XLS_TYPE_DBCELL = 0x00D7;
    final const XLS_TYPE_XF = 0x00E0;
    final const XLS_TYPE_MERGEDCELLS = 0x00E5;
    final const XLS_TYPE_MSODRAWINGGROUP = 0x00EB;
    final const XLS_TYPE_MSODRAWING = 0x00EC;
    final const XLS_TYPE_SST = 0x00FC;
    final const XLS_TYPE_LABELSST = 0x00FD;
    final const XLS_TYPE_EXTSST = 0x00FF;
    final const XLS_TYPE_EXTERNALBOOK = 0x01AE;
    final const XLS_TYPE_DATAVALIDATIONS = 0x01B2;
    final const XLS_TYPE_TXO = 0x01B6;
    final const XLS_TYPE_HYPERLINK = 0x01B8;
    final const XLS_TYPE_DATAVALIDATION = 0x01BE;
    final const XLS_TYPE_DIMENSION = 0x0200;
    final const XLS_TYPE_BLANK = 0x0201;
    final const XLS_TYPE_NUMBER = 0x0203;
    final const XLS_TYPE_LABEL = 0x0204;
    final const XLS_TYPE_BOOLERR = 0x0205;
    final const XLS_TYPE_STRING = 0x0207;
    final const XLS_TYPE_ROW = 0x0208;
    final const XLS_TYPE_INDEX = 0x020B;
    final const XLS_TYPE_ARRAY = 0x0221;
    final const XLS_TYPE_DEFAULTROWHEIGHT = 0x0225;
    final const XLS_TYPE_WINDOW2 = 0x023E;
    final const XLS_TYPE_RK = 0x027E;
    final const XLS_TYPE_STYLE = 0x0293;
    final const XLS_TYPE_FORMAT = 0x041E;
    final const XLS_TYPE_SHAREDFMLA = 0x04BC;
    final const XLS_TYPE_BOF = 0x0809;
    final const XLS_TYPE_SHEETPROTECTION = 0x0867;
    final const XLS_TYPE_RANGEPROTECTION = 0x0868;
    final const XLS_TYPE_SHEETLAYOUT = 0x0862;
    final const XLS_TYPE_XFEXT = 0x087D;
    final const XLS_TYPE_PAGELAYOUTVIEW = 0x088B;
    final const XLS_TYPE_CFHEADER = 0x01B0;
    final const XLS_TYPE_CFRULE = 0x01B1;
    final const XLS_TYPE_UNKNOWN = 0xFFFF;

    
    final const MS_BIFF_CRYPTO_NONE = 0;
    final const MS_BIFF_CRYPTO_XOR = 1;
    final const MS_BIFF_CRYPTO_RC4 = 2;

    
    final const REKEY_BLOCK = 0x400;

    
    final const BORDER_STYLE_MAP = [
        Border::BORDER_NONE, 
        Border::BORDER_THIN,  
        Border::BORDER_MEDIUM, 
        Border::BORDER_DASHED, 
        Border::BORDER_DOTTED,  
        Border::BORDER_THICK, 
        Border::BORDER_DOUBLE, 
        Border::BORDER_HAIR, 
        Border::BORDER_MEDIUMDASHED, 
        Border::BORDER_DASHDOT, 
        Border::BORDER_MEDIUMDASHDOT, 
        Border::BORDER_DASHDOTDOT, 
        Border::BORDER_MEDIUMDASHDOTDOT, 
        Border::BORDER_SLANTDASHDOT, 
        Border::BORDER_OMIT, 
        Border::BORDER_OMIT, 
    ];

    
    protected string $codepage = '';

    public function setCodepage(string $codepage): void
    {
        if (CodePage::validate($codepage) === false) {
            throw new PhpSpreadsheetException('Unknown codepage: ' . $codepage);
        }

        $this->codepage = $codepage;
    }

    public function getCodepage(): string
    {
        return $this->codepage;
    }

    
    public function canRead(string $filename): bool
    {
        if (File::testFileNoThrow($filename) === false) {
            return false;
        }

        try {
            
            $ole = new OLERead();

            
            $ole->read($filename);
            if ($ole->wrkbook === null) {
                throw new Exception('The filename ' . $filename . ' is not recognised as a Spreadsheet file');
            }

            return true;
        } catch (PhpSpreadsheetException) {
            return false;
        }
    }

    
    protected static function readRGB(string $rgb): array
    {
        
        $r = ord($rgb[0]);

        
        $g = ord($rgb[1]);

        
        $b = ord($rgb[2]);

        
        $rgb = sprintf('%02X%02X%02X', $r, $g, $b);

        return ['rgb' => $rgb];
    }

    
    protected static function readUnicodeStringShort(string $subData): array
    {
        
        $characterCount = ord($subData[0]);

        $string = self::readUnicodeString(substr($subData, 1), $characterCount);

        
        ++$string['size'];

        return $string;
    }

    
    protected static function readUnicodeStringLong(string $subData): array
    {
        
        $characterCount = self::getUInt2d($subData, 0);

        $string = self::readUnicodeString(substr($subData, 2), $characterCount);

        
        $string['size'] += 2;

        return $string;
    }

    
    protected static function readUnicodeString(string $subData, int $characterCount): array
    {
        
        
        $isCompressed = !((0x01 & ord($subData[0])) >> 0);

        
        

        
        

        
        
        
        $value = self::encodeUTF16(substr($subData, 1, $isCompressed ? $characterCount : 2 * $characterCount), $isCompressed);

        return [
            'value' => $value,
            'size' => $isCompressed ? 1 + $characterCount : 1 + 2 * $characterCount, 
        ];
    }

    
    protected static function UTF8toExcelDoubleQuoted(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    
    protected static function extractNumber(string $data): int|float
    {
        $rknumhigh = self::getInt4d($data, 4);
        $rknumlow = self::getInt4d($data, 0);
        $sign = ($rknumhigh & self::HIGH_ORDER_BIT) >> 31;
        $exp = (($rknumhigh & 0x7FF00000) >> 20) - 1023;
        $mantissa = (0x100000 | ($rknumhigh & 0x000FFFFF));
        $mantissalow1 = ($rknumlow & self::HIGH_ORDER_BIT) >> 31;
        $mantissalow2 = ($rknumlow & 0x7FFFFFFF);
        $value = $mantissa / 2 ** (20 - $exp);

        if ($mantissalow1 != 0) {
            $value += 1 / 2 ** (21 - $exp);
        }

        if ($mantissalow2 != 0) {
            $value += $mantissalow2 / 2 ** (52 - $exp);
        }
        if ($sign) {
            $value *= -1;
        }

        return $value;
    }

    protected static function getIEEE754(int $rknum): float|int
    {
        if (($rknum & 0x02) != 0) {
            $value = $rknum >> 2;
        } else {
            
            
            
            
            
            $sign = ($rknum & self::HIGH_ORDER_BIT) >> 31;
            $exp = ($rknum & 0x7FF00000) >> 20;
            $mantissa = (0x100000 | ($rknum & 0x000FFFFC));
            $value = $mantissa / 2 ** (20 - ($exp - 1023));
            if ($sign) {
                $value = -1 * $value;
            }
            
        }
        if (($rknum & 0x01) != 0) {
            $value /= 100;
        }

        return $value;
    }

    
    protected static function encodeUTF16(string $string, bool $compressed = false): string
    {
        if ($compressed) {
            $string = self::uncompressByteString($string);
        }

        return StringHelper::convertEncoding($string, 'UTF-8', 'UTF-16LE');
    }

    
    protected static function uncompressByteString(string $string): string
    {
        $uncompressedString = '';
        $strLen = strlen($string);
        for ($i = 0; $i < $strLen; ++$i) {
            $uncompressedString .= $string[$i] . "\0";
        }

        return $uncompressedString;
    }

    
    protected function decodeCodepage(string $string): string
    {
        return StringHelper::convertEncoding($string, 'UTF-8', $this->codepage);
    }

    
    public static function getUInt2d(string $data, int $pos): int
    {
        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8);
    }

    
    public static function getInt2d(string $data, int $pos): int
    {
        return unpack('s', $data[$pos] . $data[$pos + 1])[1]; 
    }

    
    public static function getInt4d(string $data, int $pos): int
    {
        
        
        
        $_or_24 = ord($data[$pos + 3]);
        if ($_or_24 >= 128) {
            
            $_ord_24 = -abs((256 - $_or_24) << 24);
        } else {
            $_ord_24 = ($_or_24 & 127) << 24;
        }

        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | $_ord_24;
    }
}
