<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;


class PageSetup
{
    
    const PAPERSIZE_LETTER = 1;
    const PAPERSIZE_LETTER_SMALL = 2;
    const PAPERSIZE_TABLOID = 3;
    const PAPERSIZE_LEDGER = 4;
    const PAPERSIZE_LEGAL = 5;
    const PAPERSIZE_STATEMENT = 6;
    const PAPERSIZE_EXECUTIVE = 7;
    const PAPERSIZE_A3 = 8;
    const PAPERSIZE_A4 = 9;
    const PAPERSIZE_A4_SMALL = 10;
    const PAPERSIZE_A5 = 11;
    const PAPERSIZE_B4 = 12;
    const PAPERSIZE_B5 = 13;
    const PAPERSIZE_FOLIO = 14;
    const PAPERSIZE_QUARTO = 15;
    const PAPERSIZE_STANDARD_1 = 16;
    const PAPERSIZE_STANDARD_2 = 17;
    const PAPERSIZE_NOTE = 18;
    const PAPERSIZE_NO9_ENVELOPE = 19;
    const PAPERSIZE_NO10_ENVELOPE = 20;
    const PAPERSIZE_NO11_ENVELOPE = 21;
    const PAPERSIZE_NO12_ENVELOPE = 22;
    const PAPERSIZE_NO14_ENVELOPE = 23;
    const PAPERSIZE_C = 24;
    const PAPERSIZE_D = 25;
    const PAPERSIZE_E = 26;
    const PAPERSIZE_DL_ENVELOPE = 27;
    const PAPERSIZE_C5_ENVELOPE = 28;
    const PAPERSIZE_C3_ENVELOPE = 29;
    const PAPERSIZE_C4_ENVELOPE = 30;
    const PAPERSIZE_C6_ENVELOPE = 31;
    const PAPERSIZE_C65_ENVELOPE = 32;
    const PAPERSIZE_B4_ENVELOPE = 33;
    const PAPERSIZE_B5_ENVELOPE = 34;
    const PAPERSIZE_B6_ENVELOPE = 35;
    const PAPERSIZE_ITALY_ENVELOPE = 36;
    const PAPERSIZE_MONARCH_ENVELOPE = 37;
    const PAPERSIZE_6_3_4_ENVELOPE = 38;
    const PAPERSIZE_US_STANDARD_FANFOLD = 39;
    const PAPERSIZE_GERMAN_STANDARD_FANFOLD = 40;
    const PAPERSIZE_GERMAN_LEGAL_FANFOLD = 41;
    const PAPERSIZE_ISO_B4 = 42;
    const PAPERSIZE_JAPANESE_DOUBLE_POSTCARD = 43;
    const PAPERSIZE_STANDARD_PAPER_1 = 44;
    const PAPERSIZE_STANDARD_PAPER_2 = 45;
    const PAPERSIZE_STANDARD_PAPER_3 = 46;
    const PAPERSIZE_INVITE_ENVELOPE = 47;
    const PAPERSIZE_LETTER_EXTRA_PAPER = 48;
    const PAPERSIZE_LEGAL_EXTRA_PAPER = 49;
    const PAPERSIZE_TABLOID_EXTRA_PAPER = 50;
    const PAPERSIZE_A4_EXTRA_PAPER = 51;
    const PAPERSIZE_LETTER_TRANSVERSE_PAPER = 52;
    const PAPERSIZE_A4_TRANSVERSE_PAPER = 53;
    const PAPERSIZE_LETTER_EXTRA_TRANSVERSE_PAPER = 54;
    const PAPERSIZE_SUPERA_SUPERA_A4_PAPER = 55;
    const PAPERSIZE_SUPERB_SUPERB_A3_PAPER = 56;
    const PAPERSIZE_LETTER_PLUS_PAPER = 57;
    const PAPERSIZE_A4_PLUS_PAPER = 58;
    const PAPERSIZE_A5_TRANSVERSE_PAPER = 59;
    const PAPERSIZE_JIS_B5_TRANSVERSE_PAPER = 60;
    const PAPERSIZE_A3_EXTRA_PAPER = 61;
    const PAPERSIZE_A5_EXTRA_PAPER = 62;
    const PAPERSIZE_ISO_B5_EXTRA_PAPER = 63;
    const PAPERSIZE_A2_PAPER = 64;
    const PAPERSIZE_A3_TRANSVERSE_PAPER = 65;
    const PAPERSIZE_A3_EXTRA_TRANSVERSE_PAPER = 66;

    
    const ORIENTATION_DEFAULT = 'default';
    const ORIENTATION_LANDSCAPE = 'landscape';
    const ORIENTATION_PORTRAIT = 'portrait';

    
    const SETPRINTRANGE_OVERWRITE = 'O';
    const SETPRINTRANGE_INSERT = 'I';

    const PAGEORDER_OVER_THEN_DOWN = 'overThenDown';
    const PAGEORDER_DOWN_THEN_OVER = 'downThenOver';

    
    private static int $paperSizeDefault = self::PAPERSIZE_LETTER;

    
    private ?int $paperSize = null;

    
    private static string $orientationDefault = self::ORIENTATION_DEFAULT;

    
    private string $orientation;

    
    private ?int $scale = 100;

    
    private bool $fitToPage = false;

    
    private ?int $fitToHeight = 1;

    
    private ?int $fitToWidth = 1;

    
    private array $columnsToRepeatAtLeft = ['', ''];

    
    private array $rowsToRepeatAtTop = [0, 0];

    
    private bool $horizontalCentered = false;

    
    private bool $verticalCentered = false;

    
    private ?string $printArea = null;

    
    private ?int $firstPageNumber = null;

    private string $pageOrder = self::PAGEORDER_DOWN_THEN_OVER;

    
    public function __construct()
    {
        $this->orientation = self::$orientationDefault;
    }

    
    public function getPaperSize(): int
    {
        return $this->paperSize ?? self::$paperSizeDefault;
    }

    
    public function setPaperSize(int $paperSize): static
    {
        $this->paperSize = $paperSize;

        return $this;
    }

    
    public static function getPaperSizeDefault(): int
    {
        return self::$paperSizeDefault;
    }

    
    public static function setPaperSizeDefault(int $paperSize): void
    {
        self::$paperSizeDefault = $paperSize;
    }

    
    public function getOrientation(): string
    {
        return $this->orientation;
    }

    
    public function setOrientation(string $orientation): static
    {
        if ($orientation === self::ORIENTATION_LANDSCAPE || $orientation === self::ORIENTATION_PORTRAIT || $orientation === self::ORIENTATION_DEFAULT) {
            $this->orientation = $orientation;
        }

        return $this;
    }

    public static function getOrientationDefault(): string
    {
        return self::$orientationDefault;
    }

    public static function setOrientationDefault(string $orientation): void
    {
        if ($orientation === self::ORIENTATION_LANDSCAPE || $orientation === self::ORIENTATION_PORTRAIT || $orientation === self::ORIENTATION_DEFAULT) {
            self::$orientationDefault = $orientation;
        }
    }

    
    public function getScale(): ?int
    {
        return $this->scale;
    }

    
    public function setScale(?int $scale, bool $update = true): static
    {
        
        
        if ($scale === null || $scale >= 0) {
            $this->scale = $scale;
            if ($update) {
                $this->fitToPage = false;
            }
        } else {
            throw new PhpSpreadsheetException('Scale must not be negative');
        }

        return $this;
    }

    
    public function getFitToPage(): bool
    {
        return $this->fitToPage;
    }

    
    public function setFitToPage(bool $fitToPage): static
    {
        $this->fitToPage = $fitToPage;

        return $this;
    }

    
    public function getFitToHeight(): ?int
    {
        return $this->fitToHeight;
    }

    
    public function setFitToHeight(?int $fitToHeight, bool $update = true): static
    {
        $this->fitToHeight = $fitToHeight;
        if ($update) {
            $this->fitToPage = true;
        }

        return $this;
    }

    
    public function getFitToWidth(): ?int
    {
        return $this->fitToWidth;
    }

    
    public function setFitToWidth(?int $value, bool $update = true): static
    {
        $this->fitToWidth = $value;
        if ($update) {
            $this->fitToPage = true;
        }

        return $this;
    }

    
    public function isColumnsToRepeatAtLeftSet(): bool
    {
        if (!empty($this->columnsToRepeatAtLeft)) {
            if ($this->columnsToRepeatAtLeft[0] != '' && $this->columnsToRepeatAtLeft[1] != '') {
                return true;
            }
        }

        return false;
    }

    
    public function getColumnsToRepeatAtLeft(): array
    {
        return $this->columnsToRepeatAtLeft;
    }

    
    public function setColumnsToRepeatAtLeft(array $columnsToRepeatAtLeft): static
    {
        $this->columnsToRepeatAtLeft = $columnsToRepeatAtLeft;

        return $this;
    }

    
    public function setColumnsToRepeatAtLeftByStartAndEnd(string $start, string $end): static
    {
        $this->columnsToRepeatAtLeft = [$start, $end];

        return $this;
    }

    
    public function isRowsToRepeatAtTopSet(): bool
    {
        if (!empty($this->rowsToRepeatAtTop)) {
            if ($this->rowsToRepeatAtTop[0] != 0 && $this->rowsToRepeatAtTop[1] != 0) {
                return true;
            }
        }

        return false;
    }

    
    public function getRowsToRepeatAtTop(): array
    {
        return $this->rowsToRepeatAtTop;
    }

    
    public function setRowsToRepeatAtTop(array $rowsToRepeatAtTop): static
    {
        $this->rowsToRepeatAtTop = $rowsToRepeatAtTop;

        return $this;
    }

    
    public function setRowsToRepeatAtTopByStartAndEnd(int $start, int $end): static
    {
        $this->rowsToRepeatAtTop = [$start, $end];

        return $this;
    }

    
    public function getHorizontalCentered(): bool
    {
        return $this->horizontalCentered;
    }

    
    public function setHorizontalCentered(bool $value): static
    {
        $this->horizontalCentered = $value;

        return $this;
    }

    
    public function getVerticalCentered(): bool
    {
        return $this->verticalCentered;
    }

    
    public function setVerticalCentered(bool $value): static
    {
        $this->verticalCentered = $value;

        return $this;
    }

    
    public function getPrintArea(int $index = 0): string
    {
        if ($index == 0) {
            return (string) $this->printArea;
        }
        $printAreas = explode(',', (string) $this->printArea);
        if (isset($printAreas[$index - 1])) {
            return $printAreas[$index - 1];
        }

        throw new PhpSpreadsheetException('Requested Print Area does not exist');
    }

    
    public function isPrintAreaSet(int $index = 0): bool
    {
        if ($index == 0) {
            return $this->printArea !== null;
        }
        $printAreas = explode(',', (string) $this->printArea);

        return isset($printAreas[$index - 1]);
    }

    
    public function clearPrintArea(int $index = 0): static
    {
        if ($index == 0) {
            $this->printArea = null;
        } else {
            $printAreas = explode(',', (string) $this->printArea);
            if (isset($printAreas[$index - 1])) {
                unset($printAreas[$index - 1]);
                $this->printArea = implode(',', $printAreas);
            }
        }

        return $this;
    }

    
    public function setPrintArea(string $value, int $index = 0, string $method = self::SETPRINTRANGE_OVERWRITE): static
    {
        if (str_contains($value, '!')) {
            throw new PhpSpreadsheetException('Cell coordinate must not specify a worksheet.');
        } elseif (!str_contains($value, ':')) {
            throw new PhpSpreadsheetException('Cell coordinate must be a range of cells.');
        } elseif (str_contains($value, '$')) {
            throw new PhpSpreadsheetException('Cell coordinate must not be absolute.');
        }
        $value = strtoupper($value);
        if (!$this->printArea) {
            $index = 0;
        }

        if ($method == self::SETPRINTRANGE_OVERWRITE) {
            if ($index == 0) {
                $this->printArea = $value;
            } else {
                $printAreas = explode(',', (string) $this->printArea);
                if ($index < 0) {
                    $index = count($printAreas) - abs($index) + 1;
                }
                if (($index <= 0) || ($index > count($printAreas))) {
                    throw new PhpSpreadsheetException('Invalid index for setting print range.');
                }
                $printAreas[$index - 1] = $value;
                $this->printArea = implode(',', $printAreas);
            }
        } elseif ($method == self::SETPRINTRANGE_INSERT) {
            if ($index == 0) {
                $this->printArea = $this->printArea ? ($this->printArea . ',' . $value) : $value;
            } else {
                $printAreas = explode(',', (string) $this->printArea);
                if ($index < 0) {
                    $index = (int) abs($index) - 1;
                }
                if ($index > count($printAreas)) {
                    throw new PhpSpreadsheetException('Invalid index for setting print range.');
                }
                $printAreas = array_merge(array_slice($printAreas, 0, $index), [$value], array_slice($printAreas, $index));
                $this->printArea = implode(',', $printAreas);
            }
        } else {
            throw new PhpSpreadsheetException('Invalid method for setting print range.');
        }

        return $this;
    }

    
    public function addPrintArea(string $value, int $index = -1): static
    {
        return $this->setPrintArea($value, $index, self::SETPRINTRANGE_INSERT);
    }

    
    public function setPrintAreaByColumnAndRow(int $column1, int $row1, int $column2, int $row2, int $index = 0, string $method = self::SETPRINTRANGE_OVERWRITE): static
    {
        return $this->setPrintArea(
            Coordinate::stringFromColumnIndex($column1) . $row1 . ':' . Coordinate::stringFromColumnIndex($column2) . $row2,
            $index,
            $method
        );
    }

    
    public function addPrintAreaByColumnAndRow(int $column1, int $row1, int $column2, int $row2, int $index = -1): static
    {
        return $this->setPrintArea(
            Coordinate::stringFromColumnIndex($column1) . $row1 . ':' . Coordinate::stringFromColumnIndex($column2) . $row2,
            $index,
            self::SETPRINTRANGE_INSERT
        );
    }

    
    public function getFirstPageNumber(): ?int
    {
        return $this->firstPageNumber;
    }

    
    public function setFirstPageNumber(?int $value): static
    {
        $this->firstPageNumber = $value;

        return $this;
    }

    
    public function resetFirstPageNumber(): static
    {
        return $this->setFirstPageNumber(null);
    }

    public function getPageOrder(): string
    {
        return $this->pageOrder;
    }

    public function setPageOrder(?string $pageOrder): self
    {
        if ($pageOrder === null || $pageOrder === self::PAGEORDER_DOWN_THEN_OVER || $pageOrder === self::PAGEORDER_OVER_THEN_DOWN) {
            $this->pageOrder = $pageOrder ?? self::PAGEORDER_DOWN_THEN_OVER;
        }

        return $this;
    }
}
