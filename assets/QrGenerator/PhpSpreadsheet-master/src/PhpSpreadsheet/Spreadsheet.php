<?php

namespace PhpOffice\PhpSpreadsheet;

use JsonSerializable;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\IValueBinder;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\Document\Security;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Iterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Spreadsheet implements JsonSerializable
{
    
    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_HIDDEN = 'hidden';
    const VISIBILITY_VERY_HIDDEN = 'veryHidden';

    private const DEFINED_NAME_IS_RANGE = false;
    private const DEFINED_NAME_IS_FORMULA = true;

    private const WORKBOOK_VIEW_VISIBILITY_VALUES = [
        self::VISIBILITY_VISIBLE,
        self::VISIBILITY_HIDDEN,
        self::VISIBILITY_VERY_HIDDEN,
    ];

    protected int $excelCalendar = Date::CALENDAR_WINDOWS_1900;

    
    private string $uniqueID;

    
    private Properties $properties;

    
    private Security $security;

    
    private array $workSheetCollection;

    
    private ?Calculation $calculationEngine;

    
    private int $activeSheetIndex;

    
    private array $definedNames;

    
    private Style $cellXfSupervisor;

    
    private array $cellXfCollection = [];

    
    private array $cellStyleXfCollection = [];

    
    private bool $hasMacros = false;

    
    private ?string $macrosCode = null;

    
    private ?string $macrosCertificate = null;

    
    private ?array $ribbonXMLData = null;

    
    private ?array $ribbonBinObjects = null;

    
    private array $unparsedLoadedData = [];

    
    private bool $showHorizontalScroll = true;

    
    private bool $showVerticalScroll = true;

    
    private bool $showSheetTabs = true;

    
    private bool $minimized = false;

    
    private bool $autoFilterDateGrouping = true;

    
    private int $firstSheetIndex = 0;

    
    private string $visibility = self::VISIBILITY_VISIBLE;

    
    private int $tabRatio = 600;

    private Theme $theme;

    private ?IValueBinder $valueBinder = null;

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    
    public function hasMacros(): bool
    {
        return $this->hasMacros;
    }

    
    public function setHasMacros(bool $hasMacros): void
    {
        $this->hasMacros = (bool) $hasMacros;
    }

    
    public function setMacrosCode(?string $macroCode): void
    {
        $this->macrosCode = $macroCode;
        $this->setHasMacros($macroCode !== null);
    }

    
    public function getMacrosCode(): ?string
    {
        return $this->macrosCode;
    }

    
    public function setMacrosCertificate(?string $certificate): void
    {
        $this->macrosCertificate = $certificate;
    }

    
    public function hasMacrosCertificate(): bool
    {
        return $this->macrosCertificate !== null;
    }

    
    public function getMacrosCertificate(): ?string
    {
        return $this->macrosCertificate;
    }

    
    public function discardMacros(): void
    {
        $this->hasMacros = false;
        $this->macrosCode = null;
        $this->macrosCertificate = null;
    }

    
    public function setRibbonXMLData(mixed $target, mixed $xmlData): void
    {
        if (is_string($target) && is_string($xmlData)) {
            $this->ribbonXMLData = ['target' => $target, 'data' => $xmlData];
        } else {
            $this->ribbonXMLData = null;
        }
    }

    
    public function getRibbonXMLData(string $what = 'all'): null|array|string 
    {
        $returnData = null;
        $what = strtolower($what);
        switch ($what) {
            case 'all':
                $returnData = $this->ribbonXMLData;

                break;
            case 'target':
            case 'data':
                if (is_array($this->ribbonXMLData)) {
                    $returnData = $this->ribbonXMLData[$what];
                }

                break;
        }

        return $returnData;
    }

    
    public function setRibbonBinObjects(mixed $binObjectsNames, mixed $binObjectsData): void
    {
        if ($binObjectsNames !== null && $binObjectsData !== null) {
            $this->ribbonBinObjects = ['names' => $binObjectsNames, 'data' => $binObjectsData];
        } else {
            $this->ribbonBinObjects = null;
        }
    }

    
    public function getUnparsedLoadedData(): array
    {
        return $this->unparsedLoadedData;
    }

    
    public function setUnparsedLoadedData(array $unparsedLoadedData): void
    {
        $this->unparsedLoadedData = $unparsedLoadedData;
    }

    
    public function getRibbonBinObjects(string $what = 'all'): ?array
    {
        $ReturnData = null;
        $what = strtolower($what);
        switch ($what) {
            case 'all':
                return $this->ribbonBinObjects;
            case 'names':
            case 'data':
                if (is_array($this->ribbonBinObjects) && is_array($this->ribbonBinObjects[$what] ?? null)) {
                    $ReturnData = $this->ribbonBinObjects[$what];
                }

                break;
            case 'types':
                if (
                    is_array($this->ribbonBinObjects)
                    && isset($this->ribbonBinObjects['data']) && is_array($this->ribbonBinObjects['data'])
                ) {
                    $tmpTypes = array_keys($this->ribbonBinObjects['data']);
                    $ReturnData = array_unique(array_map(fn (string $path): string => pathinfo($path, PATHINFO_EXTENSION), $tmpTypes));
                } else {
                    $ReturnData = []; 
                }

                break;
        }

        return $ReturnData;
    }

    
    public function hasRibbon(): bool
    {
        return $this->ribbonXMLData !== null;
    }

    
    public function hasRibbonBinObjects(): bool
    {
        return $this->ribbonBinObjects !== null;
    }

    
    public function sheetCodeNameExists(string $codeName): bool
    {
        return $this->getSheetByCodeName($codeName) !== null;
    }

    
    public function getSheetByCodeName(string $codeName): ?Worksheet
    {
        $worksheetCount = count($this->workSheetCollection);
        for ($i = 0; $i < $worksheetCount; ++$i) {
            if ($this->workSheetCollection[$i]->getCodeName() == $codeName) {
                return $this->workSheetCollection[$i];
            }
        }

        return null;
    }

    
    public function __construct()
    {
        $this->uniqueID = uniqid('', true);
        $this->calculationEngine = new Calculation($this);
        $this->theme = new Theme();

        
        $this->workSheetCollection = [];
        $this->workSheetCollection[] = new Worksheet($this);
        $this->activeSheetIndex = 0;

        
        $this->properties = new Properties();

        
        $this->security = new Security();

        
        $this->definedNames = [];

        
        $this->cellXfSupervisor = new Style(true);
        $this->cellXfSupervisor->bindParent($this);

        
        $this->addCellXf(new Style());
        $this->addCellStyleXf(new Style());
    }

    
    public function __destruct()
    {
        $this->disconnectWorksheets();
        $this->calculationEngine = null;
        $this->cellXfCollection = [];
        $this->cellStyleXfCollection = [];
        $this->definedNames = [];
    }

    
    public function disconnectWorksheets(): void
    {
        foreach ($this->workSheetCollection as $worksheet) {
            $worksheet->disconnectCells();
            unset($worksheet);
        }
        $this->workSheetCollection = [];
    }

    
    public function getCalculationEngine(): ?Calculation
    {
        return $this->calculationEngine;
    }

    
    public function getProperties(): Properties
    {
        return $this->properties;
    }

    
    public function setProperties(Properties $documentProperties): void
    {
        $this->properties = $documentProperties;
    }

    
    public function getSecurity(): Security
    {
        return $this->security;
    }

    
    public function setSecurity(Security $documentSecurity): void
    {
        $this->security = $documentSecurity;
    }

    
    public function getActiveSheet(): Worksheet
    {
        return $this->getSheet($this->activeSheetIndex);
    }

    
    public function createSheet(?int $sheetIndex = null): Worksheet
    {
        $newSheet = new Worksheet($this);
        $this->addSheet($newSheet, $sheetIndex, true);

        return $newSheet;
    }

    
    public function sheetNameExists(string $worksheetName): bool
    {
        return $this->getSheetByName($worksheetName) !== null;
    }

    public function duplicateWorksheetByTitle(string $title): Worksheet
    {
        $original = $this->getSheetByNameOrThrow($title);
        $index = $this->getIndex($original) + 1;
        $clone = clone $original;

        return $this->addSheet($clone, $index, true);
    }

    
    public function addSheet(Worksheet $worksheet, ?int $sheetIndex = null, bool $retitleIfNeeded = false): Worksheet
    {
        if ($retitleIfNeeded) {
            $title = $worksheet->getTitle();
            if ($this->sheetNameExists($title)) {
                $i = 1;
                $newTitle = "$title $i";
                while ($this->sheetNameExists($newTitle)) {
                    ++$i;
                    $newTitle = "$title $i";
                }
                $worksheet->setTitle($newTitle);
            }
        }
        if ($this->sheetNameExists($worksheet->getTitle())) {
            throw new Exception(
                "Workbook already contains a worksheet named '{$worksheet->getTitle()}'. Rename this worksheet first."
            );
        }

        if ($sheetIndex === null) {
            if ($this->activeSheetIndex < 0) {
                $this->activeSheetIndex = 0;
            }
            $this->workSheetCollection[] = $worksheet;
        } else {
            
            array_splice(
                $this->workSheetCollection,
                $sheetIndex,
                0,
                [$worksheet]
            );

            
            if ($this->activeSheetIndex >= $sheetIndex) {
                ++$this->activeSheetIndex;
            }
            if ($this->activeSheetIndex < 0) {
                $this->activeSheetIndex = 0;
            }
        }

        if ($worksheet->getParent() === null) {
            $worksheet->rebindParent($this);
        }

        return $worksheet;
    }

    
    public function removeSheetByIndex(int $sheetIndex): void
    {
        $numSheets = count($this->workSheetCollection);
        if ($sheetIndex > $numSheets - 1) {
            throw new Exception(
                "You tried to remove a sheet by the out of bounds index: {$sheetIndex}. The actual number of sheets is {$numSheets}."
            );
        }
        array_splice($this->workSheetCollection, $sheetIndex, 1);

        
        if (
            ($this->activeSheetIndex >= $sheetIndex)
            && ($this->activeSheetIndex > 0 || $numSheets <= 1)
        ) {
            --$this->activeSheetIndex;
        }
    }

    
    public function getSheet(int $sheetIndex): Worksheet
    {
        if (!isset($this->workSheetCollection[$sheetIndex])) {
            $numSheets = $this->getSheetCount();

            throw new Exception(
                "Your requested sheet index: {$sheetIndex} is out of bounds. The actual number of sheets is {$numSheets}."
            );
        }

        return $this->workSheetCollection[$sheetIndex];
    }

    
    public function getAllSheets(): array
    {
        return $this->workSheetCollection;
    }

    
    public function getSheetByName(string $worksheetName): ?Worksheet
    {
        $worksheetCount = count($this->workSheetCollection);
        for ($i = 0; $i < $worksheetCount; ++$i) {
            if (strcasecmp($this->workSheetCollection[$i]->getTitle(), trim($worksheetName, "'")) === 0) {
                return $this->workSheetCollection[$i];
            }
        }

        return null;
    }

    
    public function getSheetByNameOrThrow(string $worksheetName): Worksheet
    {
        $worksheet = $this->getSheetByName($worksheetName);
        if ($worksheet === null) {
            throw new Exception("Sheet $worksheetName does not exist.");
        }

        return $worksheet;
    }

    
    public function getIndex(Worksheet $worksheet, bool $noThrow = false): int
    {
        $wsHash = $worksheet->getHashInt();
        foreach ($this->workSheetCollection as $key => $value) {
            if ($value->getHashInt() === $wsHash) {
                return $key;
            }
        }
        if ($noThrow) {
            return -1;
        }

        throw new Exception('Sheet does not exist.');
    }

    
    public function setIndexByName(string $worksheetName, int $newIndexPosition): int
    {
        $oldIndex = $this->getIndex($this->getSheetByNameOrThrow($worksheetName));
        $worksheet = array_splice(
            $this->workSheetCollection,
            $oldIndex,
            1
        );
        array_splice(
            $this->workSheetCollection,
            $newIndexPosition,
            0,
            $worksheet
        );

        return $newIndexPosition;
    }

    
    public function getSheetCount(): int
    {
        return count($this->workSheetCollection);
    }

    
    public function getActiveSheetIndex(): int
    {
        return $this->activeSheetIndex;
    }

    
    public function setActiveSheetIndex(int $worksheetIndex): Worksheet
    {
        $numSheets = count($this->workSheetCollection);

        if ($worksheetIndex > $numSheets - 1) {
            throw new Exception(
                "You tried to set a sheet active by the out of bounds index: {$worksheetIndex}. The actual number of sheets is {$numSheets}."
            );
        }
        $this->activeSheetIndex = $worksheetIndex;

        return $this->getActiveSheet();
    }

    
    public function setActiveSheetIndexByName(string $worksheetName): Worksheet
    {
        if (($worksheet = $this->getSheetByName($worksheetName)) instanceof Worksheet) {
            $this->setActiveSheetIndex($this->getIndex($worksheet));

            return $worksheet;
        }

        throw new Exception('Workbook does not contain sheet:' . $worksheetName);
    }

    
    public function getSheetNames(): array
    {
        $returnValue = [];
        $worksheetCount = $this->getSheetCount();
        for ($i = 0; $i < $worksheetCount; ++$i) {
            $returnValue[] = $this->getSheet($i)->getTitle();
        }

        return $returnValue;
    }

    
    public function addExternalSheet(Worksheet $worksheet, ?int $sheetIndex = null): Worksheet
    {
        if ($this->sheetNameExists($worksheet->getTitle())) {
            throw new Exception("Workbook already contains a worksheet named '{$worksheet->getTitle()}'. Rename the external sheet first.");
        }

        
        $countCellXfs = count($this->cellXfCollection);

        
        foreach ($worksheet->getParentOrThrow()->getCellXfCollection() as $cellXf) {
            $this->addCellXf(clone $cellXf);
        }

        
        $worksheet->rebindParent($this);

        
        foreach ($worksheet->getCoordinates(false) as $coordinate) {
            $cell = $worksheet->getCell($coordinate);
            $cell->setXfIndex($cell->getXfIndex() + $countCellXfs);
        }

        
        foreach ($worksheet->getColumnDimensions() as $columnDimension) {
            $columnDimension->setXfIndex($columnDimension->getXfIndex() + $countCellXfs);
        }

        
        foreach ($worksheet->getRowDimensions() as $rowDimension) {
            $xfIndex = $rowDimension->getXfIndex();
            if ($xfIndex !== null) {
                $rowDimension->setXfIndex($xfIndex + $countCellXfs);
            }
        }

        return $this->addSheet($worksheet, $sheetIndex);
    }

    
    public function getNamedRanges(): array
    {
        return array_filter(
            $this->definedNames,
            fn (DefinedName $definedName): bool => $definedName->isFormula() === self::DEFINED_NAME_IS_RANGE
        );
    }

    
    public function getNamedFormulae(): array
    {
        return array_filter(
            $this->definedNames,
            fn (DefinedName $definedName): bool => $definedName->isFormula() === self::DEFINED_NAME_IS_FORMULA
        );
    }

    
    public function getDefinedNames(): array
    {
        return $this->definedNames;
    }

    
    public function addNamedRange(NamedRange $namedRange): void
    {
        $this->addDefinedName($namedRange);
    }

    
    public function addNamedFormula(NamedFormula $namedFormula): void
    {
        $this->addDefinedName($namedFormula);
    }

    
    public function addDefinedName(DefinedName $definedName): void
    {
        $upperCaseName = StringHelper::strToUpper($definedName->getName());
        if ($definedName->getScope() == null) {
            
            $this->definedNames[$upperCaseName] = $definedName;
        } else {
            
            $this->definedNames[$definedName->getScope()->getTitle() . '!' . $upperCaseName] = $definedName;
        }
    }

    
    public function getNamedRange(string $namedRange, ?Worksheet $worksheet = null): ?NamedRange
    {
        $returnValue = null;

        if ($namedRange !== '') {
            $namedRange = StringHelper::strToUpper($namedRange);
            
            $returnValue = $this->getGlobalDefinedNameByType($namedRange, self::DEFINED_NAME_IS_RANGE);
            
            $returnValue = $this->getLocalDefinedNameByType($namedRange, self::DEFINED_NAME_IS_RANGE, $worksheet) ?: $returnValue;
        }

        return $returnValue instanceof NamedRange ? $returnValue : null;
    }

    
    public function getNamedFormula(string $namedFormula, ?Worksheet $worksheet = null): ?NamedFormula
    {
        $returnValue = null;

        if ($namedFormula !== '') {
            $namedFormula = StringHelper::strToUpper($namedFormula);
            
            $returnValue = $this->getGlobalDefinedNameByType($namedFormula, self::DEFINED_NAME_IS_FORMULA);
            
            $returnValue = $this->getLocalDefinedNameByType($namedFormula, self::DEFINED_NAME_IS_FORMULA, $worksheet) ?: $returnValue;
        }

        return $returnValue instanceof NamedFormula ? $returnValue : null;
    }

    private function getGlobalDefinedNameByType(string $name, bool $type): ?DefinedName
    {
        if (isset($this->definedNames[$name]) && $this->definedNames[$name]->isFormula() === $type) {
            return $this->definedNames[$name];
        }

        return null;
    }

    private function getLocalDefinedNameByType(string $name, bool $type, ?Worksheet $worksheet = null): ?DefinedName
    {
        if (
            ($worksheet !== null) && isset($this->definedNames[$worksheet->getTitle() . '!' . $name])
            && $this->definedNames[$worksheet->getTitle() . '!' . $name]->isFormula() === $type
        ) {
            return $this->definedNames[$worksheet->getTitle() . '!' . $name];
        }

        return null;
    }

    
    public function getDefinedName(string $definedName, ?Worksheet $worksheet = null): ?DefinedName
    {
        $returnValue = null;

        if ($definedName !== '') {
            $definedName = StringHelper::strToUpper($definedName);
            
            if (isset($this->definedNames[$definedName])) {
                $returnValue = $this->definedNames[$definedName];
            }

            
            if (($worksheet !== null) && isset($this->definedNames[$worksheet->getTitle() . '!' . $definedName])) {
                $returnValue = $this->definedNames[$worksheet->getTitle() . '!' . $definedName];
            }
        }

        return $returnValue;
    }

    
    public function removeNamedRange(string $namedRange, ?Worksheet $worksheet = null): self
    {
        if ($this->getNamedRange($namedRange, $worksheet) === null) {
            return $this;
        }

        return $this->removeDefinedName($namedRange, $worksheet);
    }

    
    public function removeNamedFormula(string $namedFormula, ?Worksheet $worksheet = null): self
    {
        if ($this->getNamedFormula($namedFormula, $worksheet) === null) {
            return $this;
        }

        return $this->removeDefinedName($namedFormula, $worksheet);
    }

    
    public function removeDefinedName(string $definedName, ?Worksheet $worksheet = null): self
    {
        $definedName = StringHelper::strToUpper($definedName);

        if ($worksheet === null) {
            if (isset($this->definedNames[$definedName])) {
                unset($this->definedNames[$definedName]);
            }
        } else {
            if (isset($this->definedNames[$worksheet->getTitle() . '!' . $definedName])) {
                unset($this->definedNames[$worksheet->getTitle() . '!' . $definedName]);
            } elseif (isset($this->definedNames[$definedName])) {
                unset($this->definedNames[$definedName]);
            }
        }

        return $this;
    }

    
    public function getWorksheetIterator(): Iterator
    {
        return new Iterator($this);
    }

    
    public function copy(): self
    {
        return unserialize(serialize($this)); /
    public function __clone()
    {
        $this->uniqueID = uniqid('', true);

        $usedKeys = [];
        
        $this->cellXfSupervisor = new Style(true);
        
        $this->cellXfSupervisor->bindParent($this);
        $usedKeys['cellXfSupervisor'] = true;

        $oldCalc = $this->calculationEngine;
        $this->calculationEngine = new Calculation($this);
        if ($oldCalc !== null) {
            $this->calculationEngine
                ->setSuppressFormulaErrors(
                    $oldCalc->getSuppressFormulaErrors()
                )
                ->setCalculationCacheEnabled(
                    $oldCalc->getCalculationCacheEnabled()
                )
                ->setBranchPruningEnabled(
                    $oldCalc->getBranchPruningEnabled()
                )
                ->setInstanceArrayReturnType(
                    $oldCalc->getInstanceArrayReturnType()
                );
        }
        $usedKeys['calculationEngine'] = true;

        $currentCollection = $this->cellStyleXfCollection;
        $this->cellStyleXfCollection = [];
        foreach ($currentCollection as $item) {
            $clone = $item->exportArray();
            $style = (new Style())->applyFromArray($clone);
            $this->addCellStyleXf($style);
        }
        $usedKeys['cellStyleXfCollection'] = true;

        $currentCollection = $this->cellXfCollection;
        $this->cellXfCollection = [];
        foreach ($currentCollection as $item) {
            $clone = $item->exportArray();
            $style = (new Style())->applyFromArray($clone);
            $this->addCellXf($style);
        }
        $usedKeys['cellXfCollection'] = true;

        $currentCollection = $this->workSheetCollection;
        $this->workSheetCollection = [];
        foreach ($currentCollection as $item) {
            $clone = clone $item;
            $clone->setParent($this);
            $this->workSheetCollection[] = $clone;
        }
        $usedKeys['workSheetCollection'] = true;

        foreach (get_object_vars($this) as $key => $val) {
            if (isset($usedKeys[$key])) {
                continue;
            }
            switch ($key) {
                
                case 'definedNames':
                    
                    $currentCollection = $val;
                    $this->$key = [];
                    foreach ($currentCollection as $item) {
                        $clone = clone $item;
                        $this->{$key}[] = $clone;
                    }

                    break;
                default:
                    if (is_object($val)) {
                        $this->$key = clone $val;
                    }
            }
        }
    }

    
    public function getCellXfCollection(): array
    {
        return $this->cellXfCollection;
    }

    
    public function getCellXfByIndex(int $cellStyleIndex): Style
    {
        return $this->cellXfCollection[$cellStyleIndex];
    }

    public function getCellXfByIndexOrNull(?int $cellStyleIndex): ?Style
    {
        return ($cellStyleIndex === null) ? null : ($this->cellXfCollection[$cellStyleIndex] ?? null);
    }

    
    public function getCellXfByHashCode(string $hashcode): bool|Style
    {
        foreach ($this->cellXfCollection as $cellXf) {
            if ($cellXf->getHashCode() === $hashcode) {
                return $cellXf;
            }
        }

        return false;
    }

    
    public function cellXfExists(Style $cellStyleIndex): bool
    {
        return in_array($cellStyleIndex, $this->cellXfCollection, true);
    }

    
    public function getDefaultStyle(): Style
    {
        if (isset($this->cellXfCollection[0])) {
            return $this->cellXfCollection[0];
        }

        throw new Exception('No default style found for this workbook');
    }

    
    public function addCellXf(Style $style): void
    {
        $this->cellXfCollection[] = $style;
        $style->setIndex(count($this->cellXfCollection) - 1);
    }

    
    public function removeCellXfByIndex(int $cellStyleIndex): void
    {
        if ($cellStyleIndex > count($this->cellXfCollection) - 1) {
            throw new Exception('CellXf index is out of bounds.');
        }

        
        array_splice($this->cellXfCollection, $cellStyleIndex, 1);

        
        foreach ($this->workSheetCollection as $worksheet) {
            foreach ($worksheet->getCoordinates(false) as $coordinate) {
                $cell = $worksheet->getCell($coordinate);
                $xfIndex = $cell->getXfIndex();
                if ($xfIndex > $cellStyleIndex) {
                    
                    $cell->setXfIndex($xfIndex - 1);
                } elseif ($xfIndex == $cellStyleIndex) {
                    
                    $cell->setXfIndex(0);
                }
            }
        }
    }

    
    public function getCellXfSupervisor(): Style
    {
        return $this->cellXfSupervisor;
    }

    
    public function getCellStyleXfCollection(): array
    {
        return $this->cellStyleXfCollection;
    }

    
    public function getCellStyleXfByIndex(int $cellStyleIndex): Style
    {
        return $this->cellStyleXfCollection[$cellStyleIndex];
    }

    
    public function getCellStyleXfByHashCode(string $hashcode): bool|Style
    {
        foreach ($this->cellStyleXfCollection as $cellStyleXf) {
            if ($cellStyleXf->getHashCode() === $hashcode) {
                return $cellStyleXf;
            }
        }

        return false;
    }

    
    public function addCellStyleXf(Style $style): void
    {
        $this->cellStyleXfCollection[] = $style;
        $style->setIndex(count($this->cellStyleXfCollection) - 1);
    }

    
    public function removeCellStyleXfByIndex(int $cellStyleIndex): void
    {
        if ($cellStyleIndex > count($this->cellStyleXfCollection) - 1) {
            throw new Exception('CellStyleXf index is out of bounds.');
        }
        array_splice($this->cellStyleXfCollection, $cellStyleIndex, 1);
    }

    
    public function garbageCollect(): void
    {
        
        $countReferencesCellXf = [];
        foreach ($this->cellXfCollection as $index => $cellXf) {
            $countReferencesCellXf[$index] = 0;
        }

        foreach ($this->getWorksheetIterator() as $sheet) {
            
            foreach ($sheet->getCoordinates(false) as $coordinate) {
                $cell = $sheet->getCell($coordinate);
                ++$countReferencesCellXf[$cell->getXfIndex()];
            }

            
            foreach ($sheet->getRowDimensions() as $rowDimension) {
                if ($rowDimension->getXfIndex() !== null) {
                    ++$countReferencesCellXf[$rowDimension->getXfIndex()];
                }
            }

            
            foreach ($sheet->getColumnDimensions() as $columnDimension) {
                ++$countReferencesCellXf[$columnDimension->getXfIndex()];
            }
        }

        
        
        $countNeededCellXfs = 0;
        $map = [];
        foreach ($this->cellXfCollection as $index => $cellXf) {
            if ($countReferencesCellXf[$index] > 0 || $index == 0) { 
                ++$countNeededCellXfs;
            } else {
                unset($this->cellXfCollection[$index]);
            }
            $map[$index] = $countNeededCellXfs - 1;
        }
        $this->cellXfCollection = array_values($this->cellXfCollection);

        
        foreach ($this->cellXfCollection as $i => $cellXf) {
            $cellXf->setIndex($i);
        }

        
        if (empty($this->cellXfCollection)) {
            $this->cellXfCollection[] = new Style();
        }

        
        foreach ($this->getWorksheetIterator() as $sheet) {
            
            foreach ($sheet->getCoordinates(false) as $coordinate) {
                $cell = $sheet->getCell($coordinate);
                $cell->setXfIndex($map[$cell->getXfIndex()]);
            }

            
            foreach ($sheet->getRowDimensions() as $rowDimension) {
                if ($rowDimension->getXfIndex() !== null) {
                    $rowDimension->setXfIndex($map[$rowDimension->getXfIndex()]);
                }
            }

            
            foreach ($sheet->getColumnDimensions() as $columnDimension) {
                $columnDimension->setXfIndex($map[$columnDimension->getXfIndex()]);
            }

            
            $sheet->garbageCollect();
        }
    }

    
    public function getID(): string
    {
        return $this->uniqueID;
    }

    
    public function getShowHorizontalScroll(): bool
    {
        return $this->showHorizontalScroll;
    }

    
    public function setShowHorizontalScroll(bool $showHorizontalScroll): void
    {
        $this->showHorizontalScroll = (bool) $showHorizontalScroll;
    }

    
    public function getShowVerticalScroll(): bool
    {
        return $this->showVerticalScroll;
    }

    
    public function setShowVerticalScroll(bool $showVerticalScroll): void
    {
        $this->showVerticalScroll = (bool) $showVerticalScroll;
    }

    
    public function getShowSheetTabs(): bool
    {
        return $this->showSheetTabs;
    }

    
    public function setShowSheetTabs(bool $showSheetTabs): void
    {
        $this->showSheetTabs = (bool) $showSheetTabs;
    }

    
    public function getMinimized(): bool
    {
        return $this->minimized;
    }

    
    public function setMinimized(bool $minimized): void
    {
        $this->minimized = (bool) $minimized;
    }

    
    public function getAutoFilterDateGrouping(): bool
    {
        return $this->autoFilterDateGrouping;
    }

    
    public function setAutoFilterDateGrouping(bool $autoFilterDateGrouping): void
    {
        $this->autoFilterDateGrouping = (bool) $autoFilterDateGrouping;
    }

    
    public function getFirstSheetIndex(): int
    {
        return $this->firstSheetIndex;
    }

    
    public function setFirstSheetIndex(int $firstSheetIndex): void
    {
        if ($firstSheetIndex >= 0) {
            $this->firstSheetIndex = (int) $firstSheetIndex;
        } else {
            throw new Exception('First sheet index must be a positive integer.');
        }
    }

    
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    
    public function setVisibility(?string $visibility): void
    {
        if ($visibility === null) {
            $visibility = self::VISIBILITY_VISIBLE;
        }

        if (in_array($visibility, self::WORKBOOK_VIEW_VISIBILITY_VALUES)) {
            $this->visibility = $visibility;
        } else {
            throw new Exception('Invalid visibility value.');
        }
    }

    
    public function getTabRatio(): int
    {
        return $this->tabRatio;
    }

    
    public function setTabRatio(int $tabRatio): void
    {
        if ($tabRatio >= 0 && $tabRatio <= 1000) {
            $this->tabRatio = (int) $tabRatio;
        } else {
            throw new Exception('Tab ratio must be between 0 and 1000.');
        }
    }

    public function reevaluateAutoFilters(bool $resetToMax): void
    {
        foreach ($this->workSheetCollection as $sheet) {
            $filter = $sheet->getAutoFilter();
            if (!empty($filter->getRange())) {
                if ($resetToMax) {
                    $filter->setRangeToMaxRow();
                }
                $filter->showHideRows();
            }
        }
    }

    
    public function jsonSerialize(): mixed
    {
        throw new Exception('Spreadsheet objects cannot be json encoded');
    }

    public function resetThemeFonts(): void
    {
        $majorFontLatin = $this->theme->getMajorFontLatin();
        $minorFontLatin = $this->theme->getMinorFontLatin();
        foreach ($this->cellXfCollection as $cellStyleXf) {
            $scheme = $cellStyleXf->getFont()->getScheme();
            if ($scheme === 'major') {
                $cellStyleXf->getFont()->setName($majorFontLatin)->setScheme($scheme);
            } elseif ($scheme === 'minor') {
                $cellStyleXf->getFont()->setName($minorFontLatin)->setScheme($scheme);
            }
        }
        foreach ($this->cellStyleXfCollection as $cellStyleXf) {
            $scheme = $cellStyleXf->getFont()->getScheme();
            if ($scheme === 'major') {
                $cellStyleXf->getFont()->setName($majorFontLatin)->setScheme($scheme);
            } elseif ($scheme === 'minor') {
                $cellStyleXf->getFont()->setName($minorFontLatin)->setScheme($scheme);
            }
        }
    }

    public function getTableByName(string $tableName): ?Table
    {
        $table = null;
        foreach ($this->workSheetCollection as $sheet) {
            $table = $sheet->getTableByName($tableName);
            if ($table !== null) {
                break;
            }
        }

        return $table;
    }

    
    public function setExcelCalendar(int $baseYear): bool
    {
        if (($baseYear === Date::CALENDAR_WINDOWS_1900) || ($baseYear === Date::CALENDAR_MAC_1904)) {
            $this->excelCalendar = $baseYear;

            return true;
        }

        return false;
    }

    
    public function getExcelCalendar(): int
    {
        return $this->excelCalendar;
    }

    public function deleteLegacyDrawing(Worksheet $worksheet): void
    {
        unset($this->unparsedLoadedData['sheets'][$worksheet->getCodeName()]['legacyDrawing']);
    }

    public function getLegacyDrawing(Worksheet $worksheet): ?string
    {
        return $this->unparsedLoadedData['sheets'][$worksheet->getCodeName()]['legacyDrawing'] ?? null;
    }

    public function getValueBinder(): ?IValueBinder
    {
        return $this->valueBinder;
    }

    public function setValueBinder(?IValueBinder $valueBinder): self
    {
        $this->valueBinder = $valueBinder;

        return $this;
    }

    
    public function mergeChartCellsForPdf(): void
    {
        foreach ($this->workSheetCollection as $worksheet) {
            foreach ($worksheet->getChartCollection() as $chart) {
                $br = $chart->getBottomRightCell();
                $tl = $chart->getTopLeftCell();
                if ($br !== '' && $br !== $tl) {
                    if (!$worksheet->cellExists($br)) {
                        $worksheet->getCell($br)->setValue(' ');
                    }
                    $worksheet->mergeCells("$tl:$br");
                }
            }
        }
    }

    
    public function mergeDrawingCellsForPdf(): void
    {
        foreach ($this->workSheetCollection as $worksheet) {
            foreach ($worksheet->getDrawingCollection() as $drawing) {
                $br = $drawing->getCoordinates2();
                $tl = $drawing->getCoordinates();
                if ($br !== '' && $br !== $tl) {
                    if (!$worksheet->cellExists($br)) {
                        $worksheet->getCell($br)->setValue(' ');
                    }
                    $worksheet->mergeCells("$tl:$br");
                }
            }
        }
    }
}
