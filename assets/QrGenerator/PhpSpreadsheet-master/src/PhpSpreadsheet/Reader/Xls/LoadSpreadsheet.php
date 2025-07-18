<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Shared\CodePage;
use PhpOffice\PhpSpreadsheet\Shared\Escher as SharedEscher;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer\SpContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;
use PhpOffice\PhpSpreadsheet\Shared\Xls as SharedXls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoadSpreadsheet extends Xls
{
    
    protected function loadSpreadsheetFromFile2(string $filename, Xls $xls): Spreadsheet
    {
        
        $xls->loadOLE($filename);

        
        $xls->spreadsheet = $this->newSpreadsheet();
        $xls->spreadsheet->setValueBinder($this->valueBinder);
        $xls->spreadsheet->removeSheetByIndex(0); 
        if (!$xls->readDataOnly) {
            $xls->spreadsheet->removeCellStyleXfByIndex(0); 
            $xls->spreadsheet->removeCellXfByIndex(0); 
        }

        
        $xls->readSummaryInformation();

        
        $xls->readDocumentSummaryInformation();

        
        $xls->dataSize = strlen($xls->data);

        
        $xls->pos = 0;
        $xls->codepage = $xls->codepage ?: CodePage::DEFAULT_CODE_PAGE;
        $xls->formats = [];
        $xls->objFonts = [];
        $xls->palette = [];
        $xls->sheets = [];
        $xls->externalBooks = [];
        $xls->ref = [];
        $xls->definedname = [];
        $xls->sst = [];
        $xls->drawingGroupData = '';
        $xls->xfIndex = 0;
        $xls->mapCellXfIndex = [];
        $xls->mapCellStyleXfIndex = [];

        
        while ($xls->pos < $xls->dataSize) {
            $code = self::getUInt2d($xls->data, $xls->pos);

            match ($code) {
                self::XLS_TYPE_BOF => $xls->readBof(),
                self::XLS_TYPE_FILEPASS => $xls->readFilepass(),
                self::XLS_TYPE_CODEPAGE => $xls->readCodepage(),
                self::XLS_TYPE_DATEMODE => $xls->readDateMode(),
                self::XLS_TYPE_FONT => $xls->readFont(),
                self::XLS_TYPE_FORMAT => $xls->readFormat(),
                self::XLS_TYPE_XF => $xls->readXf(),
                self::XLS_TYPE_XFEXT => $xls->readXfExt(),
                self::XLS_TYPE_STYLE => $xls->readStyle(),
                self::XLS_TYPE_PALETTE => $xls->readPalette(),
                self::XLS_TYPE_SHEET => $xls->readSheet(),
                self::XLS_TYPE_EXTERNALBOOK => $xls->readExternalBook(),
                self::XLS_TYPE_EXTERNNAME => $xls->readExternName(),
                self::XLS_TYPE_EXTERNSHEET => $xls->readExternSheet(),
                self::XLS_TYPE_DEFINEDNAME => $xls->readDefinedName(),
                self::XLS_TYPE_MSODRAWINGGROUP => $xls->readMsoDrawingGroup(),
                self::XLS_TYPE_SST => $xls->readSst(),
                self::XLS_TYPE_EOF => $xls->readDefault(),
                default => $xls->readDefault(),
            };

            if ($code === self::XLS_TYPE_EOF) {
                break;
            }
        }

        
        
        if (!$xls->readDataOnly) {
            foreach ($xls->objFonts as $objFont) {
                if (isset($objFont->colorIndex)) {
                    $color = Color::map($objFont->colorIndex, $xls->palette, $xls->version);
                    $objFont->getColor()->setRGB($color['rgb']);
                }
            }

            foreach ($xls->spreadsheet->getCellXfCollection() as $objStyle) {
                
                $fill = $objStyle->getFill();

                if (isset($fill->startcolorIndex)) {
                    $startColor = Color::map($fill->startcolorIndex, $xls->palette, $xls->version);
                    $fill->getStartColor()->setRGB($startColor['rgb']);
                }
                if (isset($fill->endcolorIndex)) {
                    $endColor = Color::map($fill->endcolorIndex, $xls->palette, $xls->version);
                    $fill->getEndColor()->setRGB($endColor['rgb']);
                }

                
                $top = $objStyle->getBorders()->getTop();
                $right = $objStyle->getBorders()->getRight();
                $bottom = $objStyle->getBorders()->getBottom();
                $left = $objStyle->getBorders()->getLeft();
                $diagonal = $objStyle->getBorders()->getDiagonal();

                if (isset($top->colorIndex)) {
                    $borderTopColor = Color::map($top->colorIndex, $xls->palette, $xls->version);
                    $top->getColor()->setRGB($borderTopColor['rgb']);
                }
                if (isset($right->colorIndex)) {
                    $borderRightColor = Color::map($right->colorIndex, $xls->palette, $xls->version);
                    $right->getColor()->setRGB($borderRightColor['rgb']);
                }
                if (isset($bottom->colorIndex)) {
                    $borderBottomColor = Color::map($bottom->colorIndex, $xls->palette, $xls->version);
                    $bottom->getColor()->setRGB($borderBottomColor['rgb']);
                }
                if (isset($left->colorIndex)) {
                    $borderLeftColor = Color::map($left->colorIndex, $xls->palette, $xls->version);
                    $left->getColor()->setRGB($borderLeftColor['rgb']);
                }
                if (isset($diagonal->colorIndex)) {
                    $borderDiagonalColor = Color::map($diagonal->colorIndex, $xls->palette, $xls->version);
                    $diagonal->getColor()->setRGB($borderDiagonalColor['rgb']);
                }
            }
        }

        
        $escherWorkbook = null;
        if (!$xls->readDataOnly && $xls->drawingGroupData) {
            $escher = new SharedEscher();
            $reader = new Escher($escher);
            $escherWorkbook = $reader->load($xls->drawingGroupData);
        }

        
        $xls->activeSheetSet = false;
        foreach ($xls->sheets as $sheet) {
            $selectedCells = '';
            if ($sheet['sheetType'] != 0x00) {
                
                continue;
            }

            
            if (isset($xls->loadSheetsOnly) && !in_array($sheet['name'], $xls->loadSheetsOnly)) {
                continue;
            }

            
            $xls->phpSheet = $xls->spreadsheet->createSheet();
            
            
            
            $xls->phpSheet->setTitle($sheet['name'], false, false);
            $xls->phpSheet->setSheetState($sheet['sheetState']);

            $xls->pos = $sheet['offset'];

            
            $xls->isFitToPages = false;

            
            $xls->drawingData = '';

            
            $xls->objs = [];

            
            $xls->sharedFormulaParts = [];

            
            $xls->sharedFormulas = [];

            
            $xls->textObjects = [];

            
            $xls->cellNotes = [];
            $xls->textObjRef = -1;

            while ($xls->pos <= $xls->dataSize - 4) {
                $code = self::getUInt2d($xls->data, $xls->pos);

                switch ($code) {
                    case self::XLS_TYPE_BOF:
                        $xls->readBof();

                        break;
                    case self::XLS_TYPE_PRINTGRIDLINES:
                        $xls->readPrintGridlines();

                        break;
                    case self::XLS_TYPE_DEFAULTROWHEIGHT:
                        $xls->readDefaultRowHeight();

                        break;
                    case self::XLS_TYPE_SHEETPR:
                        $xls->readSheetPr();

                        break;
                    case self::XLS_TYPE_HORIZONTALPAGEBREAKS:
                        $xls->readHorizontalPageBreaks();

                        break;
                    case self::XLS_TYPE_VERTICALPAGEBREAKS:
                        $xls->readVerticalPageBreaks();

                        break;
                    case self::XLS_TYPE_HEADER:
                        $xls->readHeader();

                        break;
                    case self::XLS_TYPE_FOOTER:
                        $xls->readFooter();

                        break;
                    case self::XLS_TYPE_HCENTER:
                        $xls->readHcenter();

                        break;
                    case self::XLS_TYPE_VCENTER:
                        $xls->readVcenter();

                        break;
                    case self::XLS_TYPE_LEFTMARGIN:
                        $xls->readLeftMargin();

                        break;
                    case self::XLS_TYPE_RIGHTMARGIN:
                        $xls->readRightMargin();

                        break;
                    case self::XLS_TYPE_TOPMARGIN:
                        $xls->readTopMargin();

                        break;
                    case self::XLS_TYPE_BOTTOMMARGIN:
                        $xls->readBottomMargin();

                        break;
                    case self::XLS_TYPE_PAGESETUP:
                        $xls->readPageSetup();

                        break;
                    case self::XLS_TYPE_PROTECT:
                        $xls->readProtect();

                        break;
                    case self::XLS_TYPE_SCENPROTECT:
                        $xls->readScenProtect();

                        break;
                    case self::XLS_TYPE_OBJECTPROTECT:
                        $xls->readObjectProtect();

                        break;
                    case self::XLS_TYPE_PASSWORD:
                        $xls->readPassword();

                        break;
                    case self::XLS_TYPE_DEFCOLWIDTH:
                        $xls->readDefColWidth();

                        break;
                    case self::XLS_TYPE_COLINFO:
                        $xls->readColInfo();

                        break;
                    case self::XLS_TYPE_DIMENSION:
                        $xls->readDefault();

                        break;
                    case self::XLS_TYPE_ROW:
                        $xls->readRow();

                        break;
                    case self::XLS_TYPE_DBCELL:
                        $xls->readDefault();

                        break;
                    case self::XLS_TYPE_RK:
                        $xls->readRk();

                        break;
                    case self::XLS_TYPE_LABELSST:
                        $xls->readLabelSst();

                        break;
                    case self::XLS_TYPE_MULRK:
                        $xls->readMulRk();

                        break;
                    case self::XLS_TYPE_NUMBER:
                        $xls->readNumber();

                        break;
                    case self::XLS_TYPE_FORMULA:
                        $xls->readFormula();

                        break;
                    case self::XLS_TYPE_SHAREDFMLA:
                        $xls->readSharedFmla();

                        break;
                    case self::XLS_TYPE_BOOLERR:
                        $xls->readBoolErr();

                        break;
                    case self::XLS_TYPE_MULBLANK:
                        $xls->readMulBlank();

                        break;
                    case self::XLS_TYPE_LABEL:
                        $xls->readLabel();

                        break;
                    case self::XLS_TYPE_BLANK:
                        $xls->readBlank();

                        break;
                    case self::XLS_TYPE_MSODRAWING:
                        $xls->readMsoDrawing();

                        break;
                    case self::XLS_TYPE_OBJ:
                        $xls->readObj();

                        break;
                    case self::XLS_TYPE_WINDOW2:
                        $xls->readWindow2();

                        break;
                    case self::XLS_TYPE_PAGELAYOUTVIEW:
                        $xls->readPageLayoutView();

                        break;
                    case self::XLS_TYPE_SCL:
                        $xls->readScl();

                        break;
                    case self::XLS_TYPE_PANE:
                        $xls->readPane();

                        break;
                    case self::XLS_TYPE_SELECTION:
                        $selectedCells = $xls->readSelection();

                        break;
                    case self::XLS_TYPE_MERGEDCELLS:
                        $xls->readMergedCells();

                        break;
                    case self::XLS_TYPE_HYPERLINK:
                        $xls->readHyperLink();

                        break;
                    case self::XLS_TYPE_DATAVALIDATIONS:
                        $xls->readDataValidations();

                        break;
                    case self::XLS_TYPE_DATAVALIDATION:
                        $xls->readDataValidation();

                        break;
                    case self::XLS_TYPE_CFHEADER:
                        $cellRangeAddresses = $xls->readCFHeader();

                        break;
                    case self::XLS_TYPE_CFRULE:
                        $xls->readCFRule($cellRangeAddresses ?? []);

                        break;
                    case self::XLS_TYPE_SHEETLAYOUT:
                        $xls->readSheetLayout();

                        break;
                    case self::XLS_TYPE_SHEETPROTECTION:
                        $xls->readSheetProtection();

                        break;
                    case self::XLS_TYPE_RANGEPROTECTION:
                        $xls->readRangeProtection();

                        break;
                    case self::XLS_TYPE_NOTE:
                        $xls->readNote();

                        break;
                    case self::XLS_TYPE_TXO:
                        $xls->readTextObject();

                        break;
                    case self::XLS_TYPE_CONTINUE:
                        $xls->readContinue();

                        break;
                    case self::XLS_TYPE_EOF:
                        $xls->readDefault();

                        break 2;
                    default:
                        $xls->readDefault();

                        break;
                }
            }

            
            if (!$xls->readDataOnly && $xls->drawingData) {
                $escherWorksheet = new SharedEscher();
                $reader = new Escher($escherWorksheet);
                $escherWorksheet = $reader->load($xls->drawingData);

                
                
                $allSpContainers = method_exists($escherWorksheet, 'getDgContainer') ? $escherWorksheet->getDgContainer()->getSpgrContainer()->getAllSpContainers() : [];
            }

            
            foreach ($xls->objs as $n => $obj) {
                
                if (isset($allSpContainers[$n + 1])) {
                    $spContainer = $allSpContainers[$n + 1];

                    
                    if ($spContainer->getNestingLevel() > 1) {
                        continue;
                    }

                    
                    
                    [$startColumn, $startRow] = Coordinate::coordinateFromString($spContainer->getStartCoordinates());
                    
                    [$endColumn, $endRow] = Coordinate::coordinateFromString($spContainer->getEndCoordinates());

                    $startOffsetX = $spContainer->getStartOffsetX();
                    $startOffsetY = $spContainer->getStartOffsetY();
                    $endOffsetX = $spContainer->getEndOffsetX();
                    $endOffsetY = $spContainer->getEndOffsetY();

                    $width = SharedXls::getDistanceX($xls->phpSheet, $startColumn, $startOffsetX, $endColumn, $endOffsetX);
                    $height = SharedXls::getDistanceY($xls->phpSheet, $startRow, $startOffsetY, $endRow, $endOffsetY);

                    
                    $offsetX = (int) ($startOffsetX * SharedXls::sizeCol($xls->phpSheet, $startColumn) / 1024);
                    $offsetY = (int) ($startOffsetY * SharedXls::sizeRow($xls->phpSheet, $startRow) / 256);

                    switch ($obj['otObjType']) {
                        case 0x19:
                            
                            if (isset($xls->cellNotes[$obj['idObjID']])) {
                                

                                if (isset($xls->textObjects[$obj['idObjID']])) {
                                    $textObject = $xls->textObjects[$obj['idObjID']];
                                    $xls->cellNotes[$obj['idObjID']]['objTextData'] = $textObject;
                                }
                            }

                            break;
                        case 0x08:
                            
                            
                            
                            $BSEindex = $spContainer->getOPT(0x0104);

                            
                            
                            
                            
                            if (!$BSEindex) {
                                continue 2;
                            }

                            if ($escherWorkbook) {
                                $BSECollection = method_exists($escherWorkbook, 'getDggContainer') ? $escherWorkbook->getDggContainer()->getBstoreContainer()->getBSECollection() : [];
                                $BSE = $BSECollection[$BSEindex - 1];
                                $blipType = $BSE->getBlipType();

                                
                                if ($blip = $BSE->getBlip()) {
                                    $ih = imagecreatefromstring($blip->getData());
                                    if ($ih !== false) {
                                        $drawing = new MemoryDrawing();
                                        $drawing->setImageResource($ih);

                                        
                                        $drawing->setResizeProportional(false);
                                        $drawing->setWidth($width);
                                        $drawing->setHeight($height);
                                        $drawing->setOffsetX($offsetX);
                                        $drawing->setOffsetY($offsetY);

                                        switch ($blipType) {
                                            case BSE::BLIPTYPE_JPEG:
                                                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
                                                $drawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);

                                                break;
                                            case BSE::BLIPTYPE_PNG:
                                                imagealphablending($ih, false);
                                                imagesavealpha($ih, true);
                                                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
                                                $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);

                                                break;
                                        }

                                        $drawing->setWorksheet($xls->phpSheet);
                                        $drawing->setCoordinates($spContainer->getStartCoordinates());
                                    }
                                }
                            }

                            break;
                        default:
                            
                            break;
                    }
                }
            }

            
            if ($xls->version == self::XLS_BIFF8) {
                foreach ($xls->sharedFormulaParts as $cell => $baseCell) {
                    
                    [$column, $row] = Coordinate::coordinateFromString($cell);
                    if ($xls->getReadFilter()->readCell($column, $row, $xls->phpSheet->getTitle())) {
                        $formula = $xls->getFormulaFromStructure($xls->sharedFormulas[$baseCell], $cell);
                        $xls->phpSheet->getCell($cell)->setValueExplicit('=' . $formula, DataType::TYPE_FORMULA);
                    }
                }
            }

            if (!empty($xls->cellNotes)) {
                foreach ($xls->cellNotes as $note => $noteDetails) {
                    if (!isset($noteDetails['objTextData'])) {
                        if (isset($xls->textObjects[$note])) {
                            $textObject = $xls->textObjects[$note];
                            $noteDetails['objTextData'] = $textObject;
                        } else {
                            $noteDetails['objTextData']['text'] = '';
                        }
                    }
                    $cellAddress = str_replace('$', '', $noteDetails['cellRef']);
                    $xls->phpSheet->getComment($cellAddress)->setAuthor($noteDetails['author'])->setText($xls->parseRichText($noteDetails['objTextData']['text']));
                }
            }
            if ($selectedCells !== '') {
                $xls->phpSheet->setSelectedCells($selectedCells);
            }
        }
        if ($xls->activeSheetSet === false) {
            $xls->spreadsheet->setActiveSheetIndex(0);
        }

        
        foreach ($xls->definedname as $definedName) {
            if ($definedName['isBuiltInName']) {
                switch ($definedName['name']) {
                    case pack('C', 0x06):
                        
                        
                        $ranges = explode(',', $definedName['formula']); 

                        $extractedRanges = [];
                        $sheetName = '';
                        
                        foreach ($ranges as $range) {
                            
                            
                            
                            $explodes = Worksheet::extractSheetTitle($range, true, true);
                            $sheetName = (string) $explodes[0];
                            if (!str_contains($explodes[1], ':')) {
                                $explodes[1] = $explodes[1] . ':' . $explodes[1];
                            }
                            $extractedRanges[] = str_replace('$', '', $explodes[1]); 
                        }
                        if ($docSheet = $xls->spreadsheet->getSheetByName($sheetName)) {
                            $docSheet->getPageSetup()->setPrintArea(implode(',', $extractedRanges)); 
                        }

                        break;
                    case pack('C', 0x07):
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        $ranges = explode(',', $definedName['formula']); 
                        foreach ($ranges as $range) {
                            
                            
                            
                            if (str_contains($range, '!')) {
                                $explodes = Worksheet::extractSheetTitle($range, true, true);
                                $docSheet = $xls->spreadsheet->getSheetByName($explodes[0]);
                                if ($docSheet) {
                                    $extractedRange = $explodes[1];
                                    $extractedRange = str_replace('$', '', $extractedRange);

                                    $coordinateStrings = explode(':', $extractedRange);
                                    if (count($coordinateStrings) == 2) {
                                        [$firstColumn, $firstRow] = Coordinate::coordinateFromString($coordinateStrings[0]);
                                        [$lastColumn, $lastRow] = Coordinate::coordinateFromString($coordinateStrings[1]);
                                        $firstRow = (int) $firstRow;
                                        $lastRow = (int) $lastRow;

                                        if ($firstColumn == 'A' && $lastColumn == 'IV') {
                                            
                                            $docSheet->getPageSetup()->setRowsToRepeatAtTop([$firstRow, $lastRow]);
                                        } elseif ($firstRow == 1 && $lastRow == 65536) {
                                            
                                            $docSheet->getPageSetup()->setColumnsToRepeatAtLeft([$firstColumn, $lastColumn]);
                                        }
                                    }
                                }
                            }
                        }

                        break;
                }
            } else {
                
                
                $formula = $definedName['formula'];
                if (str_contains($formula, '!')) {
                    $explodes = Worksheet::extractSheetTitle($formula, true, true);
                    $docSheet = $xls->spreadsheet->getSheetByName($explodes[0]);
                    if ($docSheet) {
                        $extractedRange = $explodes[1];

                        $localOnly = ($definedName['scope'] === 0) ? false : true;

                        $scope = ($definedName['scope'] === 0) ? null : $xls->spreadsheet->getSheetByName($xls->sheets[$definedName['scope'] - 1]['name']);

                        $xls->spreadsheet->addNamedRange(new NamedRange((string) $definedName['name'], $docSheet, $extractedRange, $localOnly, $scope));
                    }
                }
                
                
            }
        }
        $xls->data = '';

        return $xls->spreadsheet;
    }
}
