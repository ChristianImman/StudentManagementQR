<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Shared\Escher;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer\SpContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE\Blip;
use PhpOffice\PhpSpreadsheet\Shared\OLE;
use PhpOffice\PhpSpreadsheet\Shared\OLE\PPS\File;
use PhpOffice\PhpSpreadsheet\Shared\OLE\PPS\Root;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Parser;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Workbook;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Worksheet;

class Xls extends BaseWriter
{
    
    private Spreadsheet $spreadsheet;

    
    private int $strTotal = 0;

    
    private int $strUnique = 0;

    
    private array $strTable = [];

    
    private array $colors;

    
    private Parser $parser;

    
    private array $IDCLs;

    
    private string $summaryInformation;

    
    private string $documentSummaryInformation;

    private Workbook $writerWorkbook;

    
    private array $writerWorksheets;

    
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;

        $this->parser = new Parser($spreadsheet);
    }

    
    public function save($filename, int $flags = 0): void
    {
        $this->processFlags($flags);

        
        $this->spreadsheet->garbageCollect();

        $saveDebugLog = Calculation::getInstance($this->spreadsheet)->getDebugLog()->getWriteDebugLog();
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog(false);
        $saveDateReturnType = Functions::getReturnDateType();
        Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);

        
        $this->colors = [];

        
        $this->writerWorkbook = new Workbook($this->spreadsheet, $this->strTotal, $this->strUnique, $this->strTable, $this->colors, $this->parser);

        
        $countSheets = $this->spreadsheet->getSheetCount();
        for ($i = 0; $i < $countSheets; ++$i) {
            $this->writerWorksheets[$i] = new Worksheet($this->strTotal, $this->strUnique, $this->strTable, $this->colors, $this->parser, $this->preCalculateFormulas, $this->spreadsheet->getSheet($i), $this->writerWorkbook);
        }

        
        $this->buildWorksheetEschers();
        $this->buildWorkbookEscher();

        
        
        $cellXfCollection = $this->spreadsheet->getCellXfCollection();
        for ($i = 0; $i < 15; ++$i) {
            $this->writerWorkbook->addXfWriter($cellXfCollection[0], true);
        }

        
        foreach ($this->spreadsheet->getCellXfCollection() as $style) {
            $this->writerWorkbook->addXfWriter($style, false);
        }

        
        for ($i = 0; $i < $countSheets; ++$i) {
            foreach ($this->writerWorksheets[$i]->phpSheet->getCellCollection()->getCoordinates() as $coordinate) {
                
                $cell = $this->writerWorksheets[$i]->phpSheet->getCellCollection()->get($coordinate);
                $cVal = $cell->getValue();
                if ($cVal instanceof RichText) {
                    $elements = $cVal->getRichTextElements();
                    foreach ($elements as $element) {
                        if ($element instanceof Run) {
                            $font = $element->getFont();
                            if ($font !== null) {
                                $this->writerWorksheets[$i]->fontHashIndex[$font->getHashCode()] = $this->writerWorkbook->addFont($font);
                            }
                        }
                    }
                }
            }
        }

        
        $workbookStreamName = 'Workbook';
        $OLE = new File(OLE::ascToUcs($workbookStreamName));

        
        
        $worksheetSizes = [];
        for ($i = 0; $i < $countSheets; ++$i) {
            $this->writerWorksheets[$i]->close();
            $worksheetSizes[] = $this->writerWorksheets[$i]->_datasize;
        }

        
        $OLE->append($this->writerWorkbook->writeWorkbook($worksheetSizes));

        
        for ($i = 0; $i < $countSheets; ++$i) {
            $OLE->append($this->writerWorksheets[$i]->getData());
        }

        $this->documentSummaryInformation = $this->writeDocumentSummaryInformation();
        
        if (!empty($this->documentSummaryInformation)) {
            $OLE_DocumentSummaryInformation = new File(OLE::ascToUcs(chr(5) . 'DocumentSummaryInformation'));
            $OLE_DocumentSummaryInformation->append($this->documentSummaryInformation);
        }

        $this->summaryInformation = $this->writeSummaryInformation();
        
        if (!empty($this->summaryInformation)) {
            $OLE_SummaryInformation = new File(OLE::ascToUcs(chr(5) . 'SummaryInformation'));
            $OLE_SummaryInformation->append($this->summaryInformation);
        }

        
        $arrRootData = [$OLE];
        
        if (isset($OLE_SummaryInformation)) {
            $arrRootData[] = $OLE_SummaryInformation;
        }
        
        if (isset($OLE_DocumentSummaryInformation)) {
            $arrRootData[] = $OLE_DocumentSummaryInformation;
        }

        $time = $this->spreadsheet->getProperties()->getModified();
        $root = new Root($time, $time, $arrRootData);
        
        $this->openFileHandle($filename);
        $root->save($this->fileHandle);
        $this->maybeCloseFileHandle();

        Functions::setReturnDateType($saveDateReturnType);
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog($saveDebugLog);
    }

    
    private function buildWorksheetEschers(): void
    {
        
        $blipIndex = 0;
        $lastReducedSpId = 0;
        $lastSpId = 0;

        foreach ($this->spreadsheet->getAllsheets() as $sheet) {
            
            $sheetIndex = $sheet->getParentOrThrow()->getIndex($sheet);

            
            $filterRange = $sheet->getAutoFilter()->getRange();
            if (count($sheet->getDrawingCollection()) == 0 && empty($filterRange)) {
                continue;
            }

            
            $escher = new Escher();

            
            $dgContainer = new DgContainer();

            
            $dgId = $sheet->getParentOrThrow()->getIndex($sheet) + 1;
            $dgContainer->setDgId($dgId);
            $escher->setDgContainer($dgContainer);

            
            $spgrContainer = new SpgrContainer();
            $dgContainer->setSpgrContainer($spgrContainer);

            
            $spContainer = new SpContainer();
            $spContainer->setSpgr(true);
            $spContainer->setSpType(0);
            $spContainer->setSpId(($sheet->getParentOrThrow()->getIndex($sheet) + 1) << 10);
            $spgrContainer->addChild($spContainer);

            

            $countShapes[$sheetIndex] = 0; 

            foreach ($sheet->getDrawingCollection() as $drawing) {
                ++$blipIndex;

                ++$countShapes[$sheetIndex];

                
                $spContainer = new SpContainer();

                
                $spContainer->setSpType(0x004B);
                
                $spContainer->setSpFlag(0x02);

                
                $reducedSpId = $countShapes[$sheetIndex];
                $spId = $reducedSpId | ($sheet->getParentOrThrow()->getIndex($sheet) + 1) << 10;
                $spContainer->setSpId($spId);

                
                $lastReducedSpId = $reducedSpId;

                
                $lastSpId = $spId;

                
                $spContainer->setOPT(0x4104, $blipIndex);

                
                $coordinates = $drawing->getCoordinates();
                $offsetX = $drawing->getOffsetX();
                $offsetY = $drawing->getOffsetY();
                $width = $drawing->getWidth();
                $height = $drawing->getHeight();

                $twoAnchor = \PhpOffice\PhpSpreadsheet\Shared\Xls::oneAnchor2twoAnchor($sheet, $coordinates, $offsetX, $offsetY, $width, $height);

                if (is_array($twoAnchor)) {
                    
                    $spContainer->setStartCoordinates($twoAnchor['startCoordinates']);
                    $spContainer->setStartOffsetX($twoAnchor['startOffsetX']);
                    $spContainer->setStartOffsetY($twoAnchor['startOffsetY']);
                    $spContainer->setEndCoordinates($twoAnchor['endCoordinates']);
                    $spContainer->setEndOffsetX($twoAnchor['endOffsetX']);
                    $spContainer->setEndOffsetY($twoAnchor['endOffsetY']);

                    $spgrContainer->addChild($spContainer);
                }
            }

            
            if (!empty($filterRange)) {
                $rangeBounds = Coordinate::rangeBoundaries($filterRange);
                $iNumColStart = $rangeBounds[0][0];
                $iNumColEnd = $rangeBounds[1][0];

                $iInc = $iNumColStart;
                while ($iInc <= $iNumColEnd) {
                    ++$countShapes[$sheetIndex];

                    
                    $oDrawing = new BaseDrawing();
                    
                    $cDrawing = Coordinate::stringFromColumnIndex($iInc) . $rangeBounds[0][1];
                    $oDrawing->setCoordinates($cDrawing);
                    $oDrawing->setWorksheet($sheet);

                    
                    $spContainer = new SpContainer();
                    
                    $spContainer->setSpType(0x00C9);
                    
                    $spContainer->setSpFlag(0x01);

                    
                    $reducedSpId = $countShapes[$sheetIndex];
                    $spId = $reducedSpId | ($sheet->getParentOrThrow()->getIndex($sheet) + 1) << 10;
                    $spContainer->setSpId($spId);

                    
                    $lastReducedSpId = $reducedSpId;

                    
                    $lastSpId = $spId;

                    $spContainer->setOPT(0x007F, 0x01040104); 
                    $spContainer->setOPT(0x00BF, 0x00080008); 
                    $spContainer->setOPT(0x01BF, 0x00010000); 
                    $spContainer->setOPT(0x01FF, 0x00080000); 
                    $spContainer->setOPT(0x03BF, 0x000A0000); 

                    
                    $endCoordinates = Coordinate::stringFromColumnIndex($iInc);
                    $endCoordinates .= $rangeBounds[0][1] + 1;

                    $spContainer->setStartCoordinates($cDrawing);
                    $spContainer->setStartOffsetX(0);
                    $spContainer->setStartOffsetY(0);
                    $spContainer->setEndCoordinates($endCoordinates);
                    $spContainer->setEndOffsetX(0);
                    $spContainer->setEndOffsetY(0);

                    $spgrContainer->addChild($spContainer);
                    ++$iInc;
                }
            }

            
            $this->IDCLs[$dgId] = $lastReducedSpId;

            
            $dgContainer->setLastSpId($lastSpId);

            
            $this->writerWorksheets[$sheetIndex]->setEscher($escher);
        }
    }

    private function processMemoryDrawing(BstoreContainer &$bstoreContainer, MemoryDrawing $drawing, string $renderingFunctionx): void
    {
        switch ($renderingFunctionx) {
            case MemoryDrawing::RENDERING_JPEG:
                $blipType = BSE::BLIPTYPE_JPEG;
                $renderingFunction = 'imagejpeg';

                break;
            default:
                $blipType = BSE::BLIPTYPE_PNG;
                $renderingFunction = 'imagepng';

                break;
        }

        ob_start();
        call_user_func($renderingFunction, $drawing->getImageResource()); 
        $blipData = ob_get_contents();
        ob_end_clean();

        $blip = new Blip();
        $blip->setData("$blipData");

        $BSE = new BSE();
        $BSE->setBlipType($blipType);
        $BSE->setBlip($blip);

        $bstoreContainer->addBSE($BSE);
    }

    private static int $two = 2; 

    private function processDrawing(BstoreContainer &$bstoreContainer, Drawing $drawing): void
    {
        $blipType = 0;
        $blipData = '';
        $filename = $drawing->getPath();

        $imageSize = getimagesize($filename);
        $imageFormat = empty($imageSize) ? 0 : ($imageSize[self::$two] ?? 0);

        switch ($imageFormat) {
            case 1: 
                $blipType = BSE::BLIPTYPE_PNG;
                $newImage = @imagecreatefromgif($filename);
                if ($newImage === false) {
                    throw new Exception("Unable to create image from $filename");
                }
                ob_start();
                imagepng($newImage);
                $blipData = ob_get_contents();
                ob_end_clean();

                break;
            case 2: 
                $blipType = BSE::BLIPTYPE_JPEG;
                $blipData = file_get_contents($filename);

                break;
            case 3: 
                $blipType = BSE::BLIPTYPE_PNG;
                $blipData = file_get_contents($filename);

                break;
            case 6: 
                $blipType = BSE::BLIPTYPE_PNG;
                $newImage = @imagecreatefrombmp($filename);
                if ($newImage === false) {
                    throw new Exception("Unable to create image from $filename");
                }
                ob_start();
                imagepng($newImage);
                $blipData = ob_get_contents();
                ob_end_clean();

                break;
        }
        if ($blipData) {
            $blip = new Blip();
            $blip->setData($blipData);

            $BSE = new BSE();
            $BSE->setBlipType($blipType);
            $BSE->setBlip($blip);

            $bstoreContainer->addBSE($BSE);
        }
    }

    private function processBaseDrawing(BstoreContainer &$bstoreContainer, BaseDrawing $drawing): void
    {
        if ($drawing instanceof Drawing && $drawing->getPath() !== '') {
            $this->processDrawing($bstoreContainer, $drawing);
        } elseif ($drawing instanceof MemoryDrawing) {
            $this->processMemoryDrawing($bstoreContainer, $drawing, $drawing->getRenderingFunction());
        }
    }

    private function checkForDrawings(): bool
    {
        
        $found = false;
        foreach ($this->spreadsheet->getAllSheets() as $sheet) {
            if (count($sheet->getDrawingCollection()) > 0) {
                $found = true;

                break;
            }
        }

        return $found;
    }

    
    private function buildWorkbookEscher(): void
    {
        
        if (!$this->checkForDrawings()) {
            return;
        }

        
        $escher = new Escher();

        
        $dggContainer = new DggContainer();
        $escher->setDggContainer($dggContainer);

        
        $dggContainer->setIDCLs($this->IDCLs);

        
        $spIdMax = 0;
        $totalCountShapes = 0;
        $countDrawings = 0;

        foreach ($this->spreadsheet->getAllsheets() as $sheet) {
            $sheetCountShapes = 0; 

            $addCount = 0;
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $addCount = 1;
                ++$sheetCountShapes;
                ++$totalCountShapes;

                $spId = $sheetCountShapes | ($this->spreadsheet->getIndex($sheet) + 1) << 10;
                $spIdMax = max($spId, $spIdMax);
            }
            $countDrawings += $addCount;
        }

        $dggContainer->setSpIdMax($spIdMax + 1);
        $dggContainer->setCDgSaved($countDrawings);
        $dggContainer->setCSpSaved($totalCountShapes + $countDrawings); 

        
        $bstoreContainer = new BstoreContainer();
        $dggContainer->setBstoreContainer($bstoreContainer);

        
        foreach ($this->spreadsheet->getAllsheets() as $sheet) {
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $this->processBaseDrawing($bstoreContainer, $drawing);
            }
        }

        
        $this->writerWorkbook->setEscher($escher);
    }

    
    private function writeDocumentSummaryInformation(): string
    {
        
        $data = pack('v', 0xFFFE);
        
        $data .= pack('v', 0x0000);
        
        $data .= pack('v', 0x0106);
        
        $data .= pack('v', 0x0002);
        
        $data .= pack('VVVV', 0x00, 0x00, 0x00, 0x00);
        
        $data .= pack('V', 0x0001);

        
        $data .= pack('vvvvvvvv', 0xD502, 0xD5CD, 0x2E9C, 0x101B, 0x9793, 0x0008, 0x2C2B, 0xAEF9);
        
        $data .= pack('V', 0x30);

        
        $dataSection = [];
        $dataSection_NumProps = 0;
        $dataSection_Summary = '';
        $dataSection_Content = '';

        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x01],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x02], 
            'data' => ['data' => 1252],
        ];
        ++$dataSection_NumProps;

        
        $dataProp = $this->spreadsheet->getProperties()->getCategory();
        if ($dataProp) {
            $dataSection[] = [
                'summary' => ['pack' => 'V', 'data' => 0x02],
                'offset' => ['pack' => 'V'],
                'type' => ['pack' => 'V', 'data' => 0x1E],
                'data' => ['data' => $dataProp, 'length' => strlen($dataProp)],
            ];
            ++$dataSection_NumProps;
        }
        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x17],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x03],
            'data' => ['pack' => 'V', 'data' => 0x000C0000],
        ];
        ++$dataSection_NumProps;
        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x0B],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x0B],
            'data' => ['data' => false],
        ];
        ++$dataSection_NumProps;
        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x10],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x0B],
            'data' => ['data' => false],
        ];
        ++$dataSection_NumProps;
        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x13],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x0B],
            'data' => ['data' => false],
        ];
        ++$dataSection_NumProps;
        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x16],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x0B],
            'data' => ['data' => false],
        ];
        ++$dataSection_NumProps;

        
        
        
        
        $dataProp = pack('v', 0x0001);
        $dataProp .= pack('v', 0x0000);
        
        
        $dataProp .= pack('v', 0x000A);
        $dataProp .= pack('v', 0x0000);
        
        $dataProp .= 'Worksheet' . chr(0);

        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x0D],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x101E],
            'data' => ['data' => $dataProp, 'length' => strlen($dataProp)],
        ];
        ++$dataSection_NumProps;

        
        
        
        $dataProp = pack('v', 0x0002);
        $dataProp .= pack('v', 0x0000);
        
        
        
        $dataProp .= pack('v', 0x001E);
        
        $dataProp .= pack('v', 0x0000);
        
        
        $dataProp .= pack('v', 0x0013);
        $dataProp .= pack('v', 0x0000);
        
        $dataProp .= 'Feuilles de calcul';
        
        
        $dataProp .= pack('v', 0x0300);
        
        $dataProp .= pack('v', 0x0000);
        
        $dataProp .= pack('v', 0x0100);
        $dataProp .= pack('v', 0x0000);
        $dataProp .= pack('v', 0x0000);
        $dataProp .= pack('v', 0x0000);

        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x0C],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x100C],
            'data' => ['data' => $dataProp, 'length' => strlen($dataProp)],
        ];
        ++$dataSection_NumProps;

        
        
        
        $dataSection_Content_Offset = 8 + $dataSection_NumProps * 8;
        foreach ($dataSection as $dataProp) {
            
            $dataSection_Summary .= pack($dataProp['summary']['pack'], $dataProp['summary']['data']);
            
            $dataSection_Summary .= pack($dataProp['offset']['pack'], $dataSection_Content_Offset);
            
            $dataSection_Content .= pack($dataProp['type']['pack'], $dataProp['type']['data']);
            
            if ($dataProp['type']['data'] == 0x02) { 
                $dataSection_Content .= pack('V', $dataProp['data']['data']);

                $dataSection_Content_Offset += 4 + 4;
            } elseif ($dataProp['type']['data'] == 0x03) { 
                $dataSection_Content .= pack('V', $dataProp['data']['data']);

                $dataSection_Content_Offset += 4 + 4;
            } elseif ($dataProp['type']['data'] == 0x0B) { 
                $dataSection_Content .= pack('V', (int) $dataProp['data']['data']);
                $dataSection_Content_Offset += 4 + 4;
            } elseif ($dataProp['type']['data'] == 0x1E) { 
                
                $dataProp['data']['data'] .= chr(0);
                ++$dataProp['data']['length'];
                
                $dataProp['data']['length'] = $dataProp['data']['length'] + ((4 - $dataProp['data']['length'] % 4) == 4 ? 0 : (4 - $dataProp['data']['length'] % 4));
                $dataProp['data']['data'] = str_pad($dataProp['data']['data'], $dataProp['data']['length'], chr(0), STR_PAD_RIGHT);

                $dataSection_Content .= pack('V', $dataProp['data']['length']);
                $dataSection_Content .= $dataProp['data']['data'];

                $dataSection_Content_Offset += 4 + 4 + strlen($dataProp['data']['data']);
            } else {
                $dataSection_Content .= $dataProp['data']['data'];

                $dataSection_Content_Offset += 4 + $dataProp['data']['length'];
            }
        }
        

        
        
        
        $data .= pack('V', $dataSection_Content_Offset);
        
        $data .= pack('V', $dataSection_NumProps);
        
        $data .= $dataSection_Summary;
        
        $data .= $dataSection_Content;

        return $data;
    }

    
    private function writeSummaryPropOle(float|int $dataProp, int &$dataSection_NumProps, array &$dataSection, int $sumdata, int $typdata): void
    {
        if ($dataProp) {
            $dataSection[] = [
                'summary' => ['pack' => 'V', 'data' => $sumdata],
                'offset' => ['pack' => 'V'],
                'type' => ['pack' => 'V', 'data' => $typdata], 
                'data' => ['data' => OLE::localDateToOLE($dataProp)],
            ];
            ++$dataSection_NumProps;
        }
    }

    
    private function writeSummaryProp(string $dataProp, int &$dataSection_NumProps, array &$dataSection, int $sumdata, int $typdata): void
    {
        if ($dataProp) {
            $dataSection[] = [
                'summary' => ['pack' => 'V', 'data' => $sumdata],
                'offset' => ['pack' => 'V'],
                'type' => ['pack' => 'V', 'data' => $typdata], 
                'data' => ['data' => $dataProp, 'length' => strlen($dataProp)],
            ];
            ++$dataSection_NumProps;
        }
    }

    
    private function writeSummaryInformation(): string
    {
        
        $data = pack('v', 0xFFFE);
        
        $data .= pack('v', 0x0000);
        
        $data .= pack('v', 0x0106);
        
        $data .= pack('v', 0x0002);
        
        $data .= pack('VVVV', 0x00, 0x00, 0x00, 0x00);
        
        $data .= pack('V', 0x0001);

        
        $data .= pack('vvvvvvvv', 0x85E0, 0xF29F, 0x4FF9, 0x1068, 0x91AB, 0x0008, 0x272B, 0xD9B3);
        
        $data .= pack('V', 0x30);

        
        $dataSection = [];
        $dataSection_NumProps = 0;
        $dataSection_Summary = '';
        $dataSection_Content = '';

        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x01],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x02], 
            'data' => ['data' => 1252],
        ];
        ++$dataSection_NumProps;

        $props = $this->spreadsheet->getProperties();
        $this->writeSummaryProp($props->getTitle(), $dataSection_NumProps, $dataSection, 0x02, 0x1E);
        $this->writeSummaryProp($props->getSubject(), $dataSection_NumProps, $dataSection, 0x03, 0x1E);
        $this->writeSummaryProp($props->getCreator(), $dataSection_NumProps, $dataSection, 0x04, 0x1E);
        $this->writeSummaryProp($props->getKeywords(), $dataSection_NumProps, $dataSection, 0x05, 0x1E);
        $this->writeSummaryProp($props->getDescription(), $dataSection_NumProps, $dataSection, 0x06, 0x1E);
        $this->writeSummaryProp($props->getLastModifiedBy(), $dataSection_NumProps, $dataSection, 0x08, 0x1E);
        $this->writeSummaryPropOle($props->getCreated(), $dataSection_NumProps, $dataSection, 0x0C, 0x40);
        $this->writeSummaryPropOle($props->getModified(), $dataSection_NumProps, $dataSection, 0x0D, 0x40);

        
        $dataSection[] = [
            'summary' => ['pack' => 'V', 'data' => 0x13],
            'offset' => ['pack' => 'V'],
            'type' => ['pack' => 'V', 'data' => 0x03], 
            'data' => ['data' => 0x00],
        ];
        ++$dataSection_NumProps;

        
        
        
        $dataSection_Content_Offset = 8 + $dataSection_NumProps * 8;
        foreach ($dataSection as $dataProp) {
            
            
            $dataSection_Summary .= pack($dataProp['summary']['pack'], $dataProp['summary']['data']);
            
            $dataSection_Summary .= pack($dataProp['offset']['pack'], $dataSection_Content_Offset);
            
            $dataSection_Content .= pack($dataProp['type']['pack'], $dataProp['type']['data']);
            
            if ($dataProp['type']['data'] == 0x02) { 
                $dataSection_Content .= pack('V', $dataProp['data']['data']);

                $dataSection_Content_Offset += 4 + 4;
            } elseif ($dataProp['type']['data'] == 0x03) { 
                $dataSection_Content .= pack('V', $dataProp['data']['data']);

                $dataSection_Content_Offset += 4 + 4;
            } elseif ($dataProp['type']['data'] == 0x1E) { 
                
                $dataProp['data']['data'] .= chr(0);
                ++$dataProp['data']['length'];
                
                $dataProp['data']['length'] = $dataProp['data']['length'] + ((4 - $dataProp['data']['length'] % 4) == 4 ? 0 : (4 - $dataProp['data']['length'] % 4));
                $dataProp['data']['data'] = str_pad($dataProp['data']['data'], $dataProp['data']['length'], chr(0), STR_PAD_RIGHT);

                $dataSection_Content .= pack('V', $dataProp['data']['length']);
                $dataSection_Content .= $dataProp['data']['data'];

                $dataSection_Content_Offset += 4 + 4 + strlen($dataProp['data']['data']);
            } elseif ($dataProp['type']['data'] == 0x40) { 
                $dataSection_Content .= $dataProp['data']['data'];

                $dataSection_Content_Offset += 4 + 8;
            }
            
        }
        

        
        
        
        $data .= pack('V', $dataSection_Content_Offset);
        
        $data .= pack('V', $dataSection_NumProps);
        
        $data .= $dataSection_Summary;
        
        $data .= $dataSection_Content;

        return $data;
    }
}
