<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Stringable;


class CellRange implements AddressRange, Stringable
{
    protected CellAddress $from;

    protected CellAddress $to;

    public function __construct(CellAddress $from, CellAddress $to)
    {
        $this->validateFromTo($from, $to);
    }

    private function validateFromTo(CellAddress $from, CellAddress $to): void
    {
        
        $firstColumn = min($from->columnId(), $to->columnId());
        $firstRow = min($from->rowId(), $to->rowId());
        $lastColumn = max($from->columnId(), $to->columnId());
        $lastRow = max($from->rowId(), $to->rowId());

        $fromWorksheet = $from->worksheet();
        $toWorksheet = $to->worksheet();
        $this->validateWorksheets($fromWorksheet, $toWorksheet);

        $this->from = $this->cellAddressWrapper($firstColumn, $firstRow, $fromWorksheet);
        $this->to = $this->cellAddressWrapper($lastColumn, $lastRow, $toWorksheet);
    }

    private function validateWorksheets(?Worksheet $fromWorksheet, ?Worksheet $toWorksheet): void
    {
        if ($fromWorksheet !== null && $toWorksheet !== null) {
            
            
            
            
            
            if ($fromWorksheet->getTitle() !== $toWorksheet->getTitle()) {
                throw new Exception('3d Cell Ranges are not supported');
            } elseif ($fromWorksheet->getParent() !== $toWorksheet->getParent()) {
                throw new Exception('Worksheets must be in the same spreadsheet');
            }
        }
    }

    private function cellAddressWrapper(int $column, int $row, ?Worksheet $worksheet = null): CellAddress
    {
        $cellAddress = Coordinate::stringFromColumnIndex($column) . (string) $row;

        return new class ($cellAddress, $worksheet) extends CellAddress {
            public function nextRow(int $offset = 1): CellAddress
            {
                
                $result = parent::nextRow($offset);
                $this->rowId = $result->rowId;
                $this->cellAddress = $result->cellAddress;

                return $this;
            }

            public function previousRow(int $offset = 1): CellAddress
            {
                
                $result = parent::previousRow($offset);
                $this->rowId = $result->rowId;
                $this->cellAddress = $result->cellAddress;

                return $this;
            }

            public function nextColumn(int $offset = 1): CellAddress
            {
                
                $result = parent::nextColumn($offset);
                $this->columnId = $result->columnId;
                $this->columnName = $result->columnName;
                $this->cellAddress = $result->cellAddress;

                return $this;
            }

            public function previousColumn(int $offset = 1): CellAddress
            {
                
                $result = parent::previousColumn($offset);
                $this->columnId = $result->columnId;
                $this->columnName = $result->columnName;
                $this->cellAddress = $result->cellAddress;

                return $this;
            }
        };
    }

    public function from(): CellAddress
    {
        
        $this->validateFromTo($this->from, $this->to);

        return $this->from;
    }

    public function to(): CellAddress
    {
        
        $this->validateFromTo($this->from, $this->to);

        return $this->to;
    }

    public function __toString(): string
    {
        
        $this->validateFromTo($this->from, $this->to);

        if ($this->from->cellAddress() === $this->to->cellAddress()) {
            return "{$this->from->fullCellAddress()}";
        }

        $fromAddress = $this->from->fullCellAddress();
        $toAddress = $this->to->cellAddress();

        return "{$fromAddress}:{$toAddress}";
    }
}
