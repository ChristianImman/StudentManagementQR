<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ListFunctions extends Xls
{
    
    protected function listWorksheetNames2(string $filename, Xls $xls): array
    {
        File::assertFile($filename);

        $worksheetNames = [];

        
        $xls->loadOLE($filename);

        
        $xls->dataSize = strlen($xls->data);

        $xls->pos = 0;
        $xls->sheets = [];

        
        while ($xls->pos < $xls->dataSize) {
            $code = self::getUInt2d($xls->data, $xls->pos);

            match ($code) {
                self::XLS_TYPE_BOF => $xls->readBof(),
                self::XLS_TYPE_SHEET => $xls->readSheet(),
                self::XLS_TYPE_EOF => $xls->readDefault(),
                self::XLS_TYPE_CODEPAGE => $xls->readCodepage(),
                default => $xls->readDefault(),
            };

            if ($code === self::XLS_TYPE_EOF) {
                break;
            }
        }

        foreach ($xls->sheets as $sheet) {
            if ($sheet['sheetType'] === 0x00 && is_string($sheet['name'])) {
                
                $worksheetNames[] = $sheet['name'];
            }
        }

        return $worksheetNames;
    }

    
    protected function listWorksheetInfo2(string $filename, Xls $xls): array
    {
        File::assertFile($filename);

        $worksheetInfo = [];

        
        $xls->loadOLE($filename);

        
        $xls->dataSize = strlen($xls->data);

        
        $xls->pos = 0;
        $xls->sheets = [];

        
        while ($xls->pos < $xls->dataSize) {
            $code = self::getUInt2d($xls->data, $xls->pos);

            match ($code) {
                self::XLS_TYPE_BOF => $xls->readBof(),
                self::XLS_TYPE_SHEET => $xls->readSheet(),
                self::XLS_TYPE_EOF => $xls->readDefault(),
                self::XLS_TYPE_CODEPAGE => $xls->readCodepage(),
                default => $xls->readDefault(),
            };

            if ($code === self::XLS_TYPE_EOF) {
                break;
            }
        }

        
        foreach ($xls->sheets as $sheet) {
            if ($sheet['sheetType'] !== 0x00) {
                
                
                
                continue;
            }

            $tmpInfo = [];
            $tmpInfo['worksheetName'] = StringHelper::convertToString($sheet['name']);
            $tmpInfo['lastColumnLetter'] = 'A';
            $tmpInfo['lastColumnIndex'] = 0;
            $tmpInfo['totalRows'] = 0;
            $tmpInfo['totalColumns'] = 0;
            $tmpInfo['sheetState'] = StringHelper::convertToString($sheet['sheetState']);

            $xls->pos = $sheet['offset'];

            while ($xls->pos <= $xls->dataSize - 4) {
                $code = self::getUInt2d($xls->data, $xls->pos);

                switch ($code) {
                    case self::XLS_TYPE_RK:
                    case self::XLS_TYPE_LABELSST:
                    case self::XLS_TYPE_NUMBER:
                    case self::XLS_TYPE_FORMULA:
                    case self::XLS_TYPE_BOOLERR:
                    case self::XLS_TYPE_LABEL:
                        $length = self::getUInt2d($xls->data, $xls->pos + 2);
                        $recordData = $xls->readRecordData($xls->data, $xls->pos + 4, $length);

                        
                        $xls->pos += 4 + $length;

                        $rowIndex = self::getUInt2d($recordData, 0) + 1;
                        $columnIndex = self::getUInt2d($recordData, 2);

                        $tmpInfo['totalRows'] = max($tmpInfo['totalRows'], $rowIndex);
                        $tmpInfo['lastColumnIndex'] = max($tmpInfo['lastColumnIndex'], $columnIndex);

                        break;
                    case self::XLS_TYPE_BOF:
                        $xls->readBof();

                        break;
                    case self::XLS_TYPE_EOF:
                        $xls->readDefault();

                        break 2;
                    default:
                        $xls->readDefault();

                        break;
                }
            }

            $tmpInfo['lastColumnLetter'] = Coordinate::stringFromColumnIndex($tmpInfo['lastColumnIndex'] + 1);
            $tmpInfo['totalColumns'] = $tmpInfo['lastColumnIndex'] + 1;

            $worksheetInfo[] = $tmpInfo;
        }

        return $worksheetInfo;
    }
}
