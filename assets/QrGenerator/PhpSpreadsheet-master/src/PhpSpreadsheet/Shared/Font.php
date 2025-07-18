<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font as FontStyle;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Font
{
    
    const AUTOSIZE_METHOD_APPROX = 'approx';
    const AUTOSIZE_METHOD_EXACT = 'exact';

    private const AUTOSIZE_METHODS = [
        self::AUTOSIZE_METHOD_APPROX,
        self::AUTOSIZE_METHOD_EXACT,
    ];

    
    const CHARSET_ANSI_LATIN = 0x00;
    const CHARSET_SYSTEM_DEFAULT = 0x01;
    const CHARSET_SYMBOL = 0x02;
    const CHARSET_APPLE_ROMAN = 0x4D;
    const CHARSET_ANSI_JAPANESE_SHIFTJIS = 0x80;
    const CHARSET_ANSI_KOREAN_HANGUL = 0x81;
    const CHARSET_ANSI_KOREAN_JOHAB = 0x82;
    const CHARSET_ANSI_CHINESE_SIMIPLIFIED = 0x86; 
    const CHARSET_ANSI_CHINESE_TRADITIONAL = 0x88; 
    const CHARSET_ANSI_GREEK = 0xA1;
    const CHARSET_ANSI_TURKISH = 0xA2;
    const CHARSET_ANSI_VIETNAMESE = 0xA3;
    const CHARSET_ANSI_HEBREW = 0xB1;
    const CHARSET_ANSI_ARABIC = 0xB2;
    const CHARSET_ANSI_BALTIC = 0xBA;
    const CHARSET_ANSI_CYRILLIC = 0xCC;
    const CHARSET_ANSI_THAI = 0xDD;
    const CHARSET_ANSI_LATIN_II = 0xEE;
    const CHARSET_OEM_LATIN_I = 0xFF;

    
    
    const ARIAL = 'arial.ttf';
    const ARIAL_BOLD = 'arialbd.ttf';
    const ARIAL_ITALIC = 'ariali.ttf';
    const ARIAL_BOLD_ITALIC = 'arialbi.ttf';

    const CALIBRI = 'calibri.ttf';
    const CALIBRI_BOLD = 'calibrib.ttf';
    const CALIBRI_ITALIC = 'calibrii.ttf';
    const CALIBRI_BOLD_ITALIC = 'calibriz.ttf';

    const COMIC_SANS_MS = 'comic.ttf';
    const COMIC_SANS_MS_BOLD = 'comicbd.ttf';

    const COURIER_NEW = 'cour.ttf';
    const COURIER_NEW_BOLD = 'courbd.ttf';
    const COURIER_NEW_ITALIC = 'couri.ttf';
    const COURIER_NEW_BOLD_ITALIC = 'courbi.ttf';

    const GEORGIA = 'georgia.ttf';
    const GEORGIA_BOLD = 'georgiab.ttf';
    const GEORGIA_ITALIC = 'georgiai.ttf';
    const GEORGIA_BOLD_ITALIC = 'georgiaz.ttf';

    const IMPACT = 'impact.ttf';

    const LIBERATION_SANS = 'LiberationSans-Regular.ttf';
    const LIBERATION_SANS_BOLD = 'LiberationSans-Bold.ttf';
    const LIBERATION_SANS_ITALIC = 'LiberationSans-Italic.ttf';
    const LIBERATION_SANS_BOLD_ITALIC = 'LiberationSans-BoldItalic.ttf';

    const LUCIDA_CONSOLE = 'lucon.ttf';
    const LUCIDA_SANS_UNICODE = 'l_10646.ttf';

    const MICROSOFT_SANS_SERIF = 'micross.ttf';

    const PALATINO_LINOTYPE = 'pala.ttf';
    const PALATINO_LINOTYPE_BOLD = 'palab.ttf';
    const PALATINO_LINOTYPE_ITALIC = 'palai.ttf';
    const PALATINO_LINOTYPE_BOLD_ITALIC = 'palabi.ttf';

    const SYMBOL = 'symbol.ttf';

    const TAHOMA = 'tahoma.ttf';
    const TAHOMA_BOLD = 'tahomabd.ttf';

    const TIMES_NEW_ROMAN = 'times.ttf';
    const TIMES_NEW_ROMAN_BOLD = 'timesbd.ttf';
    const TIMES_NEW_ROMAN_ITALIC = 'timesi.ttf';
    const TIMES_NEW_ROMAN_BOLD_ITALIC = 'timesbi.ttf';

    const TREBUCHET_MS = 'trebuc.ttf';
    const TREBUCHET_MS_BOLD = 'trebucbd.ttf';
    const TREBUCHET_MS_ITALIC = 'trebucit.ttf';
    const TREBUCHET_MS_BOLD_ITALIC = 'trebucbi.ttf';

    const VERDANA = 'verdana.ttf';
    const VERDANA_BOLD = 'verdanab.ttf';
    const VERDANA_ITALIC = 'verdanai.ttf';
    const VERDANA_BOLD_ITALIC = 'verdanaz.ttf';

    const FONT_FILE_NAMES = [
        'Arial' => [
            'x' => self::ARIAL,
            'xb' => self::ARIAL_BOLD,
            'xi' => self::ARIAL_ITALIC,
            'xbi' => self::ARIAL_BOLD_ITALIC,
        ],
        'Calibri' => [
            'x' => self::CALIBRI,
            'xb' => self::CALIBRI_BOLD,
            'xi' => self::CALIBRI_ITALIC,
            'xbi' => self::CALIBRI_BOLD_ITALIC,
        ],
        'Comic Sans MS' => [
            'x' => self::COMIC_SANS_MS,
            'xb' => self::COMIC_SANS_MS_BOLD,
            'xi' => self::COMIC_SANS_MS,
            'xbi' => self::COMIC_SANS_MS_BOLD,
        ],
        'Courier New' => [
            'x' => self::COURIER_NEW,
            'xb' => self::COURIER_NEW_BOLD,
            'xi' => self::COURIER_NEW_ITALIC,
            'xbi' => self::COURIER_NEW_BOLD_ITALIC,
        ],
        'Georgia' => [
            'x' => self::GEORGIA,
            'xb' => self::GEORGIA_BOLD,
            'xi' => self::GEORGIA_ITALIC,
            'xbi' => self::GEORGIA_BOLD_ITALIC,
        ],
        'Impact' => [
            'x' => self::IMPACT,
            'xb' => self::IMPACT,
            'xi' => self::IMPACT,
            'xbi' => self::IMPACT,
        ],
        'Liberation Sans' => [
            'x' => self::LIBERATION_SANS,
            'xb' => self::LIBERATION_SANS_BOLD,
            'xi' => self::LIBERATION_SANS_ITALIC,
            'xbi' => self::LIBERATION_SANS_BOLD_ITALIC,
        ],
        'Lucida Console' => [
            'x' => self::LUCIDA_CONSOLE,
            'xb' => self::LUCIDA_CONSOLE,
            'xi' => self::LUCIDA_CONSOLE,
            'xbi' => self::LUCIDA_CONSOLE,
        ],
        'Lucida Sans Unicode' => [
            'x' => self::LUCIDA_SANS_UNICODE,
            'xb' => self::LUCIDA_SANS_UNICODE,
            'xi' => self::LUCIDA_SANS_UNICODE,
            'xbi' => self::LUCIDA_SANS_UNICODE,
        ],
        'Microsoft Sans Serif' => [
            'x' => self::MICROSOFT_SANS_SERIF,
            'xb' => self::MICROSOFT_SANS_SERIF,
            'xi' => self::MICROSOFT_SANS_SERIF,
            'xbi' => self::MICROSOFT_SANS_SERIF,
        ],
        'Palatino Linotype' => [
            'x' => self::PALATINO_LINOTYPE,
            'xb' => self::PALATINO_LINOTYPE_BOLD,
            'xi' => self::PALATINO_LINOTYPE_ITALIC,
            'xbi' => self::PALATINO_LINOTYPE_BOLD_ITALIC,
        ],
        'Symbol' => [
            'x' => self::SYMBOL,
            'xb' => self::SYMBOL,
            'xi' => self::SYMBOL,
            'xbi' => self::SYMBOL,
        ],
        'Tahoma' => [
            'x' => self::TAHOMA,
            'xb' => self::TAHOMA_BOLD,
            'xi' => self::TAHOMA,
            'xbi' => self::TAHOMA_BOLD,
        ],
        'Times New Roman' => [
            'x' => self::TIMES_NEW_ROMAN,
            'xb' => self::TIMES_NEW_ROMAN_BOLD,
            'xi' => self::TIMES_NEW_ROMAN_ITALIC,
            'xbi' => self::TIMES_NEW_ROMAN_BOLD_ITALIC,
        ],
        'Trebuchet MS' => [
            'x' => self::TREBUCHET_MS,
            'xb' => self::TREBUCHET_MS_BOLD,
            'xi' => self::TREBUCHET_MS_ITALIC,
            'xbi' => self::TREBUCHET_MS_BOLD_ITALIC,
        ],
        'Verdana' => [
            'x' => self::VERDANA,
            'xb' => self::VERDANA_BOLD,
            'xi' => self::VERDANA_ITALIC,
            'xbi' => self::VERDANA_BOLD_ITALIC,
        ],
    ];

    
    private static array $extraFontArray = [];

    
    public static function setExtraFontArray(array $extraFontArray): void
    {
        self::$extraFontArray = $extraFontArray;
    }

    
    public static function getExtraFontArray(): array
    {
        return self::$extraFontArray;
    }

    
    private static string $autoSizeMethod = self::AUTOSIZE_METHOD_APPROX;

    
    private static string $trueTypeFontPath = '';

    
    public const DEFAULT_COLUMN_WIDTHS = [
        'Arial' => [
            1 => ['px' => 24, 'width' => 12.00000000, 'height' => 5.25],
            2 => ['px' => 24, 'width' => 12.00000000, 'height' => 5.25],
            3 => ['px' => 32, 'width' => 10.66406250, 'height' => 6.0],

            4 => ['px' => 32, 'width' => 10.66406250, 'height' => 6.75],
            5 => ['px' => 40, 'width' => 10.00000000, 'height' => 8.25],
            6 => ['px' => 48, 'width' => 9.59765625, 'height' => 8.25],
            7 => ['px' => 48, 'width' => 9.59765625, 'height' => 9.0],
            8 => ['px' => 56, 'width' => 9.33203125, 'height' => 11.25],
            9 => ['px' => 64, 'width' => 9.14062500, 'height' => 12.0],
            10 => ['px' => 64, 'width' => 9.14062500, 'height' => 12.75],
        ],
        'Calibri' => [
            1 => ['px' => 24, 'width' => 12.00000000, 'height' => 5.25],
            2 => ['px' => 24, 'width' => 12.00000000, 'height' => 5.25],
            3 => ['px' => 32, 'width' => 10.66406250, 'height' => 6.00],
            4 => ['px' => 32, 'width' => 10.66406250, 'height' => 6.75],
            5 => ['px' => 40, 'width' => 10.00000000, 'height' => 8.25],
            6 => ['px' => 48, 'width' => 9.59765625, 'height' => 8.25],
            7 => ['px' => 48, 'width' => 9.59765625, 'height' => 9.0],
            8 => ['px' => 56, 'width' => 9.33203125, 'height' => 11.25],
            9 => ['px' => 56, 'width' => 9.33203125, 'height' => 12.0],
            10 => ['px' => 64, 'width' => 9.14062500, 'height' => 12.75],
            11 => ['px' => 64, 'width' => 9.14062500, 'height' => 15.0],
        ],
        'Verdana' => [
            1 => ['px' => 24, 'width' => 12.00000000, 'height' => 5.25],
            2 => ['px' => 24, 'width' => 12.00000000, 'height' => 5.25],
            3 => ['px' => 32, 'width' => 10.66406250, 'height' => 6.0],
            4 => ['px' => 32, 'width' => 10.66406250, 'height' => 6.75],
            5 => ['px' => 40, 'width' => 10.00000000, 'height' => 8.25],
            6 => ['px' => 48, 'width' => 9.59765625, 'height' => 8.25],
            7 => ['px' => 48, 'width' => 9.59765625, 'height' => 9.0],
            8 => ['px' => 64, 'width' => 9.14062500, 'height' => 10.5],
            9 => ['px' => 72, 'width' => 9.00000000, 'height' => 11.25],
            10 => ['px' => 72, 'width' => 9.00000000, 'height' => 12.75],
        ],
    ];

    
    public static function setAutoSizeMethod(string $method): bool
    {
        if (!in_array($method, self::AUTOSIZE_METHODS)) {
            return false;
        }
        self::$autoSizeMethod = $method;

        return true;
    }

    
    public static function getAutoSizeMethod(): string
    {
        return self::$autoSizeMethod;
    }

    
    public static function setTrueTypeFontPath(string $folderPath): void
    {
        self::$trueTypeFontPath = $folderPath;
    }

    
    public static function getTrueTypeFontPath(): string
    {
        return self::$trueTypeFontPath;
    }

    
    private static null|float|int $paddingAmountExact = null;

    
    public static function setPaddingAmountExact(null|float|int $paddingAmountExact): void
    {
        self::$paddingAmountExact = $paddingAmountExact;
    }

    
    public static function getPaddingAmountExact(): null|float|int
    {
        return self::$paddingAmountExact;
    }

    
    public static function calculateColumnWidth(
        FontStyle $font,
        $cellText = '',
        int $rotation = 0,
        ?FontStyle $defaultFont = null,
        bool $filterAdjustment = false,
        int $indentAdjustment = 0
    ): float {
        
        if ($cellText instanceof RichText) {
            $cellText = $cellText->getPlainText();
        }

        
        $cellText = (string) $cellText;
        if (str_contains($cellText, "\n")) {
            $lineTexts = explode("\n", $cellText);
            $lineWidths = [];
            foreach ($lineTexts as $lineText) {
                $lineWidths[] = self::calculateColumnWidth($font, $lineText, $rotation = 0, $defaultFont, $filterAdjustment);
            }

            return max($lineWidths); 
        }

        
        $approximate = self::$autoSizeMethod === self::AUTOSIZE_METHOD_APPROX;
        $columnWidth = 0;
        if (!$approximate) {
            try {
                $columnWidthAdjust = ceil(
                    self::getTextWidthPixelsExact(
                        str_repeat('n', 1 * (($filterAdjustment ? 3 : 1) + ($indentAdjustment * 2))),
                        $font,
                        0
                    ) * 1.07
                );

                
                
                $columnWidth = self::getTextWidthPixelsExact($cellText, $font, $rotation) + (self::$paddingAmountExact ?? $columnWidthAdjust);
            } catch (PhpSpreadsheetException) {
                $approximate = true;
            }
        }

        if ($approximate) {
            $columnWidthAdjust = self::getTextWidthPixelsApprox(
                str_repeat('n', 1 * (($filterAdjustment ? 3 : 1) + ($indentAdjustment * 2))),
                $font,
                0
            );
            
            
            $columnWidth = self::getTextWidthPixelsApprox($cellText, $font, $rotation) + $columnWidthAdjust;
        }

        
        $columnWidth = Drawing::pixelsToCellDimension((int) $columnWidth, $defaultFont ?? new FontStyle());

        
        return round($columnWidth, 4);
    }

    
    public static function getTextWidthPixelsExact(string $text, FontStyle $font, int $rotation = 0): float
    {
        
        
        $fontFile = self::getTrueTypeFontFileFromFont($font);
        $textBox = imagettfbbox($font->getSize() ?? 10.0, $rotation, $fontFile, $text);
        if ($textBox === false) {
            
            throw new PhpSpreadsheetException('imagettfbbox failed');
            
        }

        
        $lowerLeftCornerX = $textBox[0];
        $lowerRightCornerX = $textBox[2];
        $upperRightCornerX = $textBox[4];
        $upperLeftCornerX = $textBox[6];

        
        return round(max($lowerRightCornerX - $upperLeftCornerX, $upperRightCornerX - $lowerLeftCornerX), 4);
    }

    
    public static function getTextWidthPixelsApprox(string $columnText, FontStyle $font, int $rotation = 0): int
    {
        $fontName = $font->getName();
        $fontSize = $font->getSize();

        
        
        
        switch ($fontName) {
            case 'Arial':
                
                $columnWidth = (int) (8 * StringHelper::countCharactersDbcs($columnText));
                $columnWidth = $columnWidth * $fontSize / 10; 

                break;
            case 'Verdana':
                
                $columnWidth = (int) (8 * StringHelper::countCharactersDbcs($columnText));
                $columnWidth = $columnWidth * $fontSize / 10; 

                break;
            default:
                
                
                $columnWidth = (int) (8.26 * StringHelper::countCharactersDbcs($columnText));
                $columnWidth = $columnWidth * $fontSize / 11; 

                break;
        }

        
        if ($rotation !== 0) {
            if ($rotation == Alignment::TEXTROTATION_STACK_PHPSPREADSHEET) {
                
                $columnWidth = 4; 
            } else {
                
                $columnWidth = $columnWidth * cos(deg2rad($rotation))
                                + $fontSize * abs(sin(deg2rad($rotation))) / 5; 
            }
        }

        
        return (int) $columnWidth;
    }

    
    public static function fontSizeToPixels(float|int $fontSizeInPoints): int
    {
        return (int) ((4 / 3) * $fontSizeInPoints);
    }

    
    public static function inchSizeToPixels(int|float $sizeInInch): int|float
    {
        return $sizeInInch * 96;
    }

    
    public static function centimeterSizeToPixels(int|float $sizeInCm): float
    {
        return $sizeInCm * 37.795275591;
    }

    
    public static function getTrueTypeFontFileFromFont(FontStyle $font, bool $checkPath = true): string
    {
        if ($checkPath && (!file_exists(self::$trueTypeFontPath) || !is_dir(self::$trueTypeFontPath))) {
            throw new PhpSpreadsheetException('Valid directory to TrueType Font files not specified');
        }

        $name = $font->getName();
        $fontArray = array_merge(self::FONT_FILE_NAMES, self::$extraFontArray);
        if (!isset($fontArray[$name])) {
            throw new PhpSpreadsheetException('Unknown font name "' . $name . '". Cannot map to TrueType font file');
        }
        $bold = $font->getBold();
        $italic = $font->getItalic();
        $index = 'x';
        if ($bold) {
            $index .= 'b';
        }
        if ($italic) {
            $index .= 'i';
        }
        $fontFile = $fontArray[$name][$index];

        $separator = '';
        if (mb_strlen(self::$trueTypeFontPath) > 1 && mb_substr(self::$trueTypeFontPath, -1) !== '/' && mb_substr(self::$trueTypeFontPath, -1) !== '\\') {
            $separator = DIRECTORY_SEPARATOR;
        }
        $fontFileAbsolute = preg_match('~^([A-Za-z]:)?[/\\\]~', $fontFile) === 1;
        if (!$fontFileAbsolute) {
            $fontFile = self::findFontFile(self::$trueTypeFontPath, $fontFile) ?? self::$trueTypeFontPath . $separator . $fontFile;
        }

        
        if ($checkPath && !file_exists($fontFile) && !$fontFileAbsolute) {
            $alternateName = $name;
            if ($index !== 'x' && $fontArray[$name][$index] !== $fontArray[$name]['x']) {
                
                
                
                
                
                
                
                
                
                if ($index === 'xb') {
                    $alternateName .= ' Bold';
                } elseif ($index === 'xi') {
                    $alternateName .= ' Italic';
                } elseif ($fontArray[$name]['xb'] === $fontArray[$name]['xbi']) {
                    $alternateName .= ' Bold';
                } else {
                    $alternateName .= ' Bold Italic';
                }
            }
            $fontFile = self::$trueTypeFontPath . $separator . $alternateName . '.ttf';
            if (!file_exists($fontFile)) {
                throw new PhpSpreadsheetException('TrueType Font file not found');
            }
        }

        return $fontFile;
    }

    public const CHARSET_FROM_FONT_NAME = [
        'EucrosiaUPC' => self::CHARSET_ANSI_THAI,
        'Wingdings' => self::CHARSET_SYMBOL,
        'Wingdings 2' => self::CHARSET_SYMBOL,
        'Wingdings 3' => self::CHARSET_SYMBOL,
    ];

    
    public static function getCharsetFromFontName(string $fontName): int
    {
        return self::CHARSET_FROM_FONT_NAME[$fontName] ?? self::CHARSET_ANSI_LATIN;
    }

    
    public static function getDefaultColumnWidthByFont(FontStyle $font, bool $returnAsPixels = false): float|int
    {
        if (isset(self::DEFAULT_COLUMN_WIDTHS[$font->getName()][$font->getSize()])) {
            
            $columnWidth = $returnAsPixels
                ? self::DEFAULT_COLUMN_WIDTHS[$font->getName()][$font->getSize()]['px']
                    : self::DEFAULT_COLUMN_WIDTHS[$font->getName()][$font->getSize()]['width'];
        } else {
            
            
            $columnWidth = $returnAsPixels
                ? self::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['px']
                    : self::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['width'];
            $columnWidth = $columnWidth * $font->getSize() / 11;

            
            if ($returnAsPixels) {
                $columnWidth = (int) round($columnWidth);
            }
        }

        return $columnWidth;
    }

    
    public static function getDefaultRowHeightByFont(FontStyle $font): float
    {
        $name = $font->getName();
        $size = $font->getSize();
        if (isset(self::DEFAULT_COLUMN_WIDTHS[$name][$size])) {
            $rowHeight = self::DEFAULT_COLUMN_WIDTHS[$name][$size]['height'];
        } elseif ($name === 'Arial' || $name === 'Verdana') {
            $rowHeight = self::DEFAULT_COLUMN_WIDTHS[$name][10]['height'] * $size / 10.0;
        } else {
            $rowHeight = self::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['height'] * $size / 11.0;
        }

        return $rowHeight;
    }

    private static function findFontFile(string $startDirectory, string $desiredFont): ?string
    {
        $fontPath = null;
        if ($startDirectory === '') {
            return null;
        }
        if (file_exists("$startDirectory/$desiredFont")) {
            $fontPath = "$startDirectory/$desiredFont";
        } else {
            $iterations = 0;
            $it = new RecursiveDirectoryIterator(
                $startDirectory,
                RecursiveDirectoryIterator::SKIP_DOTS
                | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            );
            foreach (
                new RecursiveIteratorIterator(
                    $it,
                    RecursiveIteratorIterator::LEAVES_ONLY,
                    RecursiveIteratorIterator::CATCH_GET_CHILD
                ) as $filex
            ) {
                
                $file = $filex;
                if (basename($file) === $desiredFont) {
                    $fontPath = $file;

                    break;
                }
                ++$iterations;
                if ($iterations > 5000) {
                    
                    break;
                    
                }
            }
        }

        return $fontPath;
    }
}
