<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use SimpleXMLElement;

class WorkbookView
{
    private Spreadsheet $spreadsheet;

    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

    public function viewSettings(SimpleXMLElement $xmlWorkbook, string $mainNS, array $mapSheetId, bool $readDataOnly): void
    {
        
        $this->spreadsheet->setActiveSheetIndex(0);

        $workbookView = $xmlWorkbook->children($mainNS)->bookViews->workbookView;
        if ($readDataOnly !== true && !empty($workbookView)) {
            $workbookViewAttributes = self::testSimpleXml(self::getAttributes($workbookView));
            
            $activeTab = (int) $workbookViewAttributes->activeTab; 
            
            if (isset($mapSheetId[$activeTab])) {
                $this->spreadsheet->setActiveSheetIndex($mapSheetId[$activeTab]);
            }

            $this->horizontalScroll($workbookViewAttributes);
            $this->verticalScroll($workbookViewAttributes);
            $this->sheetTabs($workbookViewAttributes);
            $this->minimized($workbookViewAttributes);
            $this->autoFilterDateGrouping($workbookViewAttributes);
            $this->firstSheet($workbookViewAttributes);
            $this->visibility($workbookViewAttributes);
            $this->tabRatio($workbookViewAttributes);
        }
    }

    public static function testSimpleXml(mixed $value): SimpleXMLElement
    {
        return ($value instanceof SimpleXMLElement)
            ? $value
            : new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
    }

    public static function getAttributes(?SimpleXMLElement $value, string $ns = ''): SimpleXMLElement
    {
        return self::testSimpleXml($value === null ? $value : $value->attributes($ns));
    }

    
    private function castXsdBooleanToBool(string $xsdBoolean): bool
    {
        if ($xsdBoolean === 'false') {
            return false;
        }

        return (bool) $xsdBoolean;
    }

    private function horizontalScroll(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->showHorizontalScroll)) {
            $showHorizontalScroll = (string) $workbookViewAttributes->showHorizontalScroll;
            $this->spreadsheet->setShowHorizontalScroll($this->castXsdBooleanToBool($showHorizontalScroll));
        }
    }

    private function verticalScroll(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->showVerticalScroll)) {
            $showVerticalScroll = (string) $workbookViewAttributes->showVerticalScroll;
            $this->spreadsheet->setShowVerticalScroll($this->castXsdBooleanToBool($showVerticalScroll));
        }
    }

    private function sheetTabs(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->showSheetTabs)) {
            $showSheetTabs = (string) $workbookViewAttributes->showSheetTabs;
            $this->spreadsheet->setShowSheetTabs($this->castXsdBooleanToBool($showSheetTabs));
        }
    }

    private function minimized(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->minimized)) {
            $minimized = (string) $workbookViewAttributes->minimized;
            $this->spreadsheet->setMinimized($this->castXsdBooleanToBool($minimized));
        }
    }

    private function autoFilterDateGrouping(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->autoFilterDateGrouping)) {
            $autoFilterDateGrouping = (string) $workbookViewAttributes->autoFilterDateGrouping;
            $this->spreadsheet->setAutoFilterDateGrouping($this->castXsdBooleanToBool($autoFilterDateGrouping));
        }
    }

    private function firstSheet(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->firstSheet)) {
            $firstSheet = (string) $workbookViewAttributes->firstSheet;
            $this->spreadsheet->setFirstSheetIndex((int) $firstSheet);
        }
    }

    private function visibility(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->visibility)) {
            $visibility = (string) $workbookViewAttributes->visibility;
            $this->spreadsheet->setVisibility($visibility);
        }
    }

    private function tabRatio(SimpleXMLElement $workbookViewAttributes): void
    {
        if (isset($workbookViewAttributes->tabRatio)) {
            $tabRatio = (string) $workbookViewAttributes->tabRatio;
            $this->spreadsheet->setTabRatio((int) $tabRatio);
        }
    }
}
