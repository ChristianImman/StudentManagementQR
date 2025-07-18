<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

use PhpOffice\PhpSpreadsheet\Calculation\Engine\BranchPruner;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\CyclicReferenceStack;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\Logger;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\Operands;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Token\Stack;
use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\DefinedName;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\ReferenceHelper;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

class Calculation extends CalculationLocale
{
    
    
    
    const CALCULATION_REGEXP_NUMBER = '[-+]?\d*\.?\d+(e[-+]?\d+)?';
    
    const CALCULATION_REGEXP_STRING = '"(?:[^"]|"")*"';
    
    const CALCULATION_REGEXP_OPENBRACE = '\(';
    
    const CALCULATION_REGEXP_FUNCTION = '@?(?:_xlfn\.)?(?:_xlws\.)?([\p{L}][\p{L}\p{N}\.]*)[\s]*\(';
    
    const CALCULATION_REGEXP_CELLREF = '((([^\s,!&%^\/\*\+<>=:`-]*)|(\'(?:[^\']|\'[^!])+?\')|(\"(?:[^\"]|\"[^!])+?\"))!)?\$?\b([a-z]{1,3})\$?(\d{1,7})(?![\w.])';
    
    const CALCULATION_REGEXP_CELLREF_SPILL = '/' . self::CALCULATION_REGEXP_CELLREF . '
    
    const CALCULATION_REGEXP_CELLREF_RELATIVE = '((([^\s\(,!&%^\/\*\+<>=:`-]*)|(\'(?:[^\']|\'[^!])+?\')|(\"(?:[^\"]|\"[^!])+?\"))!)?(\$?\b[a-z]{1,3})(\$?\d{1,7})(?![\w.])';
    const CALCULATION_REGEXP_COLUMN_RANGE = '(((([^\s\(,!&%^\/\*\+<>=:`-]*)|(\'(?:[^\']|\'[^!])+?\')|(\".(?:[^\"]|\"[^!])?\"))!)?(\$?[a-z]{1,3})):(?![.*])';
    const CALCULATION_REGEXP_ROW_RANGE = '(((([^\s\(,!&%^\/\*\+<>=:`-]*)|(\'(?:[^\']|\'[^!])+?\')|(\"(?:[^\"]|\"[^!])+?\"))!)?(\$?[1-9][0-9]{0,6})):(?![.*])';
    
    
    const CALCULATION_REGEXP_COLUMNRANGE_RELATIVE = '(\$?[a-z]{1,3}):(\$?[a-z]{1,3})';
    const CALCULATION_REGEXP_ROWRANGE_RELATIVE = '(\$?\d{1,7}):(\$?\d{1,7})';
    
    const CALCULATION_REGEXP_DEFINEDNAME = '((([^\s,!&%^\/\*\+<>=-]*)|(\'(?:[^\']|\'[^!])+?\')|(\"(?:[^\"]|\"[^!])+?\"))!)?([_\p{L}][_\p{L}\p{N}\.]*)';
    
    const CALCULATION_REGEXP_STRUCTURED_REFERENCE = '([\p{L}_\\\][\p{L}\p{N}\._]+)?(\[(?:[^\d\]+-])?)';
    
    const CALCULATION_REGEXP_ERROR = '\

    
    const RETURN_ARRAY_AS_ERROR = 'error';
    const RETURN_ARRAY_AS_VALUE = 'value';
    const RETURN_ARRAY_AS_ARRAY = 'array';

    
    private static string $returnArrayAsType = self::RETURN_ARRAY_AS_VALUE;

    
    private ?string $instanceArrayReturnType = null;

    
    private static ?Calculation $instance = null;

    
    private ?Spreadsheet $spreadsheet;

    
    private array $calculationCache = [];

    
    private bool $calculationCacheEnabled = true;

    private BranchPruner $branchPruner;

    private bool $branchPruningEnabled = true;

    
    private const CALCULATION_OPERATORS = [
        '+' => true, '-' => true, '*' => true, '/' => true,
        '^' => true, '&' => true, '%' => false, '~' => false,
        '>' => true, '<' => true, '=' => true, '>=' => true,
        '<=' => true, '<>' => true, '∩' => true, '∪' => true,
        ':' => true,
    ];

    
    private const BINARY_OPERATORS = [
        '+' => true, '-' => true, '*' => true, '/' => true,
        '^' => true, '&' => true, '>' => true, '<' => true,
        '=' => true, '>=' => true, '<=' => true, '<>' => true,
        '∩' => true, '∪' => true, ':' => true,
    ];

    
    private Logger $debugLog;

    private bool $suppressFormulaErrors = false;

    private bool $processingAnchorArray = false;

    
    public ?string $formulaError = null;

    
    private CyclicReferenceStack $cyclicReferenceStack;

    private array $cellStack = [];

    
    private int $cyclicFormulaCounter = 1;

    private string $cyclicFormulaCell = '';

    
    public int $cyclicFormulaCount = 1;

    
    private const EXCEL_CONSTANTS = [
        'TRUE' => true,
        'FALSE' => false,
        'NULL' => null,
    ];

    public static function keyInExcelConstants(string $key): bool
    {
        return array_key_exists($key, self::EXCEL_CONSTANTS);
    }

    public static function getExcelConstants(string $key): bool|null
    {
        return self::EXCEL_CONSTANTS[$key];
    }

    
    private static array $controlFunctions = [
        'MKMATRIX' => [
            'argumentCount' => '*',
            'functionCall' => [Internal\MakeMatrix::class, 'make'],
        ],
        'NAME.ERROR' => [
            'argumentCount' => '*',
            'functionCall' => [ExcelError::class, 'NAME'],
        ],
        'WILDCARDMATCH' => [
            'argumentCount' => '2',
            'functionCall' => [Internal\WildcardMatch::class, 'compare'],
        ],
    ];

    public function __construct(?Spreadsheet $spreadsheet = null)
    {
        $this->spreadsheet = $spreadsheet;
        $this->cyclicReferenceStack = new CyclicReferenceStack();
        $this->debugLog = new Logger($this->cyclicReferenceStack);
        $this->branchPruner = new BranchPruner($this->branchPruningEnabled);
    }

    
    public static function getInstance(?Spreadsheet $spreadsheet = null): self
    {
        if ($spreadsheet !== null) {
            $instance = $spreadsheet->getCalculationEngine();
            if (isset($instance)) {
                return $instance;
            }
        }

        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    
    public function flushInstance(): void
    {
        $this->clearCalculationCache();
        $this->branchPruner->clearBranchStore();
    }

    
    public function getDebugLog(): Logger
    {
        return $this->debugLog;
    }

    
    final public function __clone()
    {
        throw new Exception('Cloning the calculation engine is not allowed!');
    }

    
    public static function setArrayReturnType(string $returnType): bool
    {
        if (
            ($returnType == self::RETURN_ARRAY_AS_VALUE)
            || ($returnType == self::RETURN_ARRAY_AS_ERROR)
            || ($returnType == self::RETURN_ARRAY_AS_ARRAY)
        ) {
            self::$returnArrayAsType = $returnType;

            return true;
        }

        return false;
    }

    
    public static function getArrayReturnType(): string
    {
        return self::$returnArrayAsType;
    }

    
    public function setInstanceArrayReturnType(string $returnType): bool
    {
        if (
            ($returnType == self::RETURN_ARRAY_AS_VALUE)
            || ($returnType == self::RETURN_ARRAY_AS_ERROR)
            || ($returnType == self::RETURN_ARRAY_AS_ARRAY)
        ) {
            $this->instanceArrayReturnType = $returnType;

            return true;
        }

        return false;
    }

    
    public function getInstanceArrayReturnType(): string
    {
        return $this->instanceArrayReturnType ?? self::$returnArrayAsType;
    }

    
    public function getCalculationCacheEnabled(): bool
    {
        return $this->calculationCacheEnabled;
    }

    
    public function setCalculationCacheEnabled(bool $calculationCacheEnabled): self
    {
        $this->calculationCacheEnabled = $calculationCacheEnabled;
        $this->clearCalculationCache();

        return $this;
    }

    
    public function enableCalculationCache(): void
    {
        $this->setCalculationCacheEnabled(true);
    }

    
    public function disableCalculationCache(): void
    {
        $this->setCalculationCacheEnabled(false);
    }

    
    public function clearCalculationCache(): void
    {
        $this->calculationCache = [];
    }

    
    public function clearCalculationCacheForWorksheet(string $worksheetName): void
    {
        if (isset($this->calculationCache[$worksheetName])) {
            unset($this->calculationCache[$worksheetName]);
        }
    }

    
    public function renameCalculationCacheForWorksheet(string $fromWorksheetName, string $toWorksheetName): void
    {
        if (isset($this->calculationCache[$fromWorksheetName])) {
            $this->calculationCache[$toWorksheetName] = &$this->calculationCache[$fromWorksheetName];
            unset($this->calculationCache[$fromWorksheetName]);
        }
    }

    public function getBranchPruningEnabled(): bool
    {
        return $this->branchPruningEnabled;
    }

    public function setBranchPruningEnabled(mixed $enabled): self
    {
        $this->branchPruningEnabled = (bool) $enabled;
        $this->branchPruner = new BranchPruner($this->branchPruningEnabled);

        return $this;
    }

    public function enableBranchPruning(): void
    {
        $this->setBranchPruningEnabled(true);
    }

    public function disableBranchPruning(): void
    {
        $this->setBranchPruningEnabled(false);
    }

    
    public static function wrapResult(mixed $value): mixed
    {
        if (is_string($value)) {
            
            if (preg_match('/^' . self::CALCULATION_REGEXP_ERROR . '$/i', $value, $match)) {
                
                return $value;
            }

            
            return self::FORMULA_STRING_QUOTE . $value . self::FORMULA_STRING_QUOTE;
        } elseif ((is_float($value)) && ((is_nan($value)) || (is_infinite($value)))) {
            
            return ExcelError::NAN();
        }

        return $value;
    }

    
    public static function unwrapResult(mixed $value): mixed
    {
        if (is_string($value)) {
            if ((isset($value[0])) && ($value[0] == self::FORMULA_STRING_QUOTE) && (substr($value, -1) == self::FORMULA_STRING_QUOTE)) {
                return substr($value, 1, -1);
            }
            
        } elseif ((is_float($value)) && ((is_nan($value)) || (is_infinite($value)))) {
            return ExcelError::NAN();
        }

        return $value;
    }

    
    public function calculate(?Cell $cell = null): mixed
    {
        try {
            return $this->calculateCellValue($cell);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    
    public function calculateCellValue(?Cell $cell = null, bool $resetLog = true): mixed
    {
        if ($cell === null) {
            return null;
        }

        if ($resetLog) {
            
            $this->formulaError = null;
            $this->debugLog->clearLog();
            $this->cyclicReferenceStack->clear();
            $this->cyclicFormulaCounter = 1;
        }

        
        $this->cellStack[] = [
            'sheet' => $cell->getWorksheet()->getTitle(),
            'cell' => $cell->getCoordinate(),
        ];

        $cellAddressAttempted = false;
        $cellAddress = null;

        try {
            $value = $cell->getValue();
            if (is_string($value) && $cell->getDataType() === DataType::TYPE_FORMULA) {
                $value = preg_replace_callback(
                    self::CALCULATION_REGEXP_CELLREF_SPILL,
                    fn (array $matches) => 'ANCHORARRAY(' . substr($matches[0], 0, -1) . ')',
                    $value
                );
            }
            $result = self::unwrapResult($this->_calculateFormulaValue($value, $cell->getCoordinate(), $cell)); /
    public function parseFormula(string $formula): array|bool
    {
        $formula = preg_replace_callback(
            self::CALCULATION_REGEXP_CELLREF_SPILL,
            fn (array $matches) => 'ANCHORARRAY(' . substr($matches[0], 0, -1) . ')',
            $formula
        ) ?? $formula;
        
        
        $formula = trim($formula);
        if ((!isset($formula[0])) || ($formula[0] != '=')) {
            return [];
        }
        $formula = ltrim(substr($formula, 1));
        if (!isset($formula[0])) {
            return [];
        }

        
        return $this->internalParseFormula($formula);
    }

    
    public function calculateFormula(string $formula, ?string $cellID = null, ?Cell $cell = null): mixed
    {
        
        $this->formulaError = null;
        $this->debugLog->clearLog();
        $this->cyclicReferenceStack->clear();

        $resetCache = $this->getCalculationCacheEnabled();
        if ($this->spreadsheet !== null && $cellID === null && $cell === null) {
            $cellID = 'A1';
            $cell = $this->spreadsheet->getActiveSheet()->getCell($cellID);
        } else {
            
            
            $this->calculationCacheEnabled = false;
        }

        
        try {
            $result = self::unwrapResult($this->_calculateFormulaValue($formula, $cellID, $cell));
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        if ($this->spreadsheet === null) {
            
            $this->calculationCacheEnabled = $resetCache;
        }

        return $result;
    }

    public function getValueFromCache(string $cellReference, mixed &$cellValue): bool
    {
        $this->debugLog->writeDebugLog('Testing cache value for cell %s', $cellReference);
        
        
        if (($this->calculationCacheEnabled) && (isset($this->calculationCache[$cellReference]))) {
            $this->debugLog->writeDebugLog('Retrieving value for cell %s from cache', $cellReference);
            

            $cellValue = $this->calculationCache[$cellReference];

            return true;
        }

        return false;
    }

    public function saveValueToCache(string $cellReference, mixed $cellValue): void
    {
        if ($this->calculationCacheEnabled) {
            $this->calculationCache[$cellReference] = $cellValue;
        }
    }

    
    public function _calculateFormulaValue(string $formula, ?string $cellID = null, ?Cell $cell = null, bool $ignoreQuotePrefix = false): mixed
    {
        $cellValue = null;

        
        if ($cell !== null && $ignoreQuotePrefix === false && $cell->getStyle()->getQuotePrefix() === true) {
            return self::wrapResult((string) $formula);
        }

        if (preg_match('/^=\s*cmd\s*\|/miu', $formula) !== 0) {
            return self::wrapResult($formula);
        }

        
        
        $formula = trim($formula);
        if ($formula === '' || $formula[0] !== '=') {
            return self::wrapResult($formula);
        }
        $formula = ltrim(substr($formula, 1));
        if (!isset($formula[0])) {
            return self::wrapResult($formula);
        }

        $pCellParent = ($cell !== null) ? $cell->getWorksheet() : null;
        $wsTitle = ($pCellParent !== null) ? $pCellParent->getTitle() : "\x00Wrk";
        $wsCellReference = $wsTitle . '!' . $cellID;

        if (($cellID !== null) && ($this->getValueFromCache($wsCellReference, $cellValue))) {
            return $cellValue;
        }
        $this->debugLog->writeDebugLog('Evaluating formula for cell %s', $wsCellReference);

        if (($wsTitle[0] !== "\x00") && ($this->cyclicReferenceStack->onStack($wsCellReference))) {
            if ($this->cyclicFormulaCount <= 0) {
                $this->cyclicFormulaCell = '';

                return $this->raiseFormulaError('Cyclic Reference in Formula');
            } elseif ($this->cyclicFormulaCell === $wsCellReference) {
                ++$this->cyclicFormulaCounter;
                if ($this->cyclicFormulaCounter >= $this->cyclicFormulaCount) {
                    $this->cyclicFormulaCell = '';

                    return $cellValue;
                }
            } elseif ($this->cyclicFormulaCell == '') {
                if ($this->cyclicFormulaCounter >= $this->cyclicFormulaCount) {
                    return $cellValue;
                }
                $this->cyclicFormulaCell = $wsCellReference;
            }
        }

        $this->debugLog->writeDebugLog('Formula for cell %s is %s', $wsCellReference, $formula);
        
        $this->cyclicReferenceStack->push($wsCellReference);

        $cellValue = $this->processTokenStack($this->internalParseFormula($formula, $cell), $cellID, $cell);
        $this->cyclicReferenceStack->pop();

        
        if ($cellID !== null) {
            $this->saveValueToCache($wsCellReference, $cellValue);
        }

        
        return $cellValue;
    }

    
    public static function checkMatrixOperands(mixed &$operand1, mixed &$operand2, int $resize = 1): array
    {
        
        
        if (!is_array($operand1)) {
            if (is_array($operand2)) {
                [$matrixRows, $matrixColumns] = self::getMatrixDimensions($operand2);
                $operand1 = array_fill(0, $matrixRows, array_fill(0, $matrixColumns, $operand1));
                $resize = 0;
            } else {
                $operand1 = [$operand1];
                $operand2 = [$operand2];
            }
        } elseif (!is_array($operand2)) {
            [$matrixRows, $matrixColumns] = self::getMatrixDimensions($operand1);
            $operand2 = array_fill(0, $matrixRows, array_fill(0, $matrixColumns, $operand2));
            $resize = 0;
        }

        [$matrix1Rows, $matrix1Columns] = self::getMatrixDimensions($operand1);
        [$matrix2Rows, $matrix2Columns] = self::getMatrixDimensions($operand2);
        if ($resize === 3) {
            $resize = 2;
        } elseif (($matrix1Rows == $matrix2Columns) && ($matrix2Rows == $matrix1Columns)) {
            $resize = 1;
        }

        if ($resize == 2) {
            
            self::resizeMatricesExtend($operand1, $operand2, $matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns);
        } elseif ($resize == 1) {
            
            
            
            self::resizeMatricesShrink($operand1, $operand2, $matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns);
        }
        [$matrix1Rows, $matrix1Columns] = self::getMatrixDimensions($operand1);
        [$matrix2Rows, $matrix2Columns] = self::getMatrixDimensions($operand2);

        return [$matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns];
    }

    
    public static function getMatrixDimensions(array &$matrix): array
    {
        $matrixRows = count($matrix);
        $matrixColumns = 0;
        foreach ($matrix as $rowKey => $rowValue) {
            if (!is_array($rowValue)) {
                $matrix[$rowKey] = [$rowValue];
                $matrixColumns = max(1, $matrixColumns);
            } else {
                $matrix[$rowKey] = array_values($rowValue);
                $matrixColumns = max(count($rowValue), $matrixColumns);
            }
        }
        $matrix = array_values($matrix);

        return [$matrixRows, $matrixColumns];
    }

    
    private static function resizeMatricesShrink(array &$matrix1, array &$matrix2, int $matrix1Rows, int $matrix1Columns, int $matrix2Rows, int $matrix2Columns): void
    {
        if (($matrix2Columns < $matrix1Columns) || ($matrix2Rows < $matrix1Rows)) {
            if ($matrix2Rows < $matrix1Rows) {
                for ($i = $matrix2Rows; $i < $matrix1Rows; ++$i) {
                    unset($matrix1[$i]);
                }
            }
            if ($matrix2Columns < $matrix1Columns) {
                for ($i = 0; $i < $matrix1Rows; ++$i) {
                    for ($j = $matrix2Columns; $j < $matrix1Columns; ++$j) {
                        unset($matrix1[$i][$j]);
                    }
                }
            }
        }

        if (($matrix1Columns < $matrix2Columns) || ($matrix1Rows < $matrix2Rows)) {
            if ($matrix1Rows < $matrix2Rows) {
                for ($i = $matrix1Rows; $i < $matrix2Rows; ++$i) {
                    unset($matrix2[$i]);
                }
            }
            if ($matrix1Columns < $matrix2Columns) {
                for ($i = 0; $i < $matrix2Rows; ++$i) {
                    for ($j = $matrix1Columns; $j < $matrix2Columns; ++$j) {
                        unset($matrix2[$i][$j]);
                    }
                }
            }
        }
    }

    
    private static function resizeMatricesExtend(array &$matrix1, array &$matrix2, int $matrix1Rows, int $matrix1Columns, int $matrix2Rows, int $matrix2Columns): void
    {
        if (($matrix2Columns < $matrix1Columns) || ($matrix2Rows < $matrix1Rows)) {
            if ($matrix2Columns < $matrix1Columns) {
                for ($i = 0; $i < $matrix2Rows; ++$i) {
                    $x = $matrix2[$i][$matrix2Columns - 1];
                    for ($j = $matrix2Columns; $j < $matrix1Columns; ++$j) {
                        $matrix2[$i][$j] = $x;
                    }
                }
            }
            if ($matrix2Rows < $matrix1Rows) {
                $x = $matrix2[$matrix2Rows - 1];
                for ($i = 0; $i < $matrix1Rows; ++$i) {
                    $matrix2[$i] = $x;
                }
            }
        }

        if (($matrix1Columns < $matrix2Columns) || ($matrix1Rows < $matrix2Rows)) {
            if ($matrix1Columns < $matrix2Columns) {
                for ($i = 0; $i < $matrix1Rows; ++$i) {
                    $x = $matrix1[$i][$matrix1Columns - 1];
                    for ($j = $matrix1Columns; $j < $matrix2Columns; ++$j) {
                        $matrix1[$i][$j] = $x;
                    }
                }
            }
            if ($matrix1Rows < $matrix2Rows) {
                $x = $matrix1[$matrix1Rows - 1];
                for ($i = 0; $i < $matrix2Rows; ++$i) {
                    $matrix1[$i] = $x;
                }
            }
        }
    }

    
    private function showValue(mixed $value): mixed
    {
        if ($this->debugLog->getWriteDebugLog()) {
            $testArray = Functions::flattenArray($value);
            if (count($testArray) == 1) {
                $value = array_pop($testArray);
            }

            if (is_array($value)) {
                $returnMatrix = [];
                $pad = $rpad = ', ';
                foreach ($value as $row) {
                    if (is_array($row)) {
                        $returnMatrix[] = implode($pad, array_map([$this, 'showValue'], $row));
                        $rpad = '; ';
                    } else {
                        $returnMatrix[] = $this->showValue($row);
                    }
                }

                return '{ ' . implode($rpad, $returnMatrix) . ' }';
            } elseif (is_string($value) && (trim($value, self::FORMULA_STRING_QUOTE) == $value)) {
                return self::FORMULA_STRING_QUOTE . $value . self::FORMULA_STRING_QUOTE;
            } elseif (is_bool($value)) {
                return ($value) ? self::$localeBoolean['TRUE'] : self::$localeBoolean['FALSE'];
            } elseif ($value === null) {
                return self::$localeBoolean['NULL'];
            }
        }

        return Functions::flattenSingleValue($value);
    }

    
    private function showTypeDetails(mixed $value): ?string
    {
        if ($this->debugLog->getWriteDebugLog()) {
            $testArray = Functions::flattenArray($value);
            if (count($testArray) == 1) {
                $value = array_pop($testArray);
            }

            if ($value === null) {
                return 'a NULL value';
            } elseif (is_float($value)) {
                $typeString = 'a floating point number';
            } elseif (is_int($value)) {
                $typeString = 'an integer number';
            } elseif (is_bool($value)) {
                $typeString = 'a boolean';
            } elseif (is_array($value)) {
                $typeString = 'a matrix';
            } else {
                
                if ($value == '') {
                    return 'an empty string';
                } elseif ($value[0] == '
                    return 'a ' . $value . ' error';
                }
                $typeString = 'a string';
            }

            return $typeString . ' with a value of ' . StringHelper::convertToString($this->showValue($value));
        }

        return null;
    }

    
    private function convertMatrixReferences(string $formula): false|string
    {
        static $matrixReplaceFrom = [self::FORMULA_OPEN_MATRIX_BRACE, ';', self::FORMULA_CLOSE_MATRIX_BRACE];
        static $matrixReplaceTo = ['MKMATRIX(MKMATRIX(', '),MKMATRIX(', '))'];

        
        if (str_contains($formula, self::FORMULA_OPEN_MATRIX_BRACE)) {
            
            if (str_contains($formula, self::FORMULA_STRING_QUOTE)) {
                
                
                $temp = explode(self::FORMULA_STRING_QUOTE, $formula);
                
                $openCount = $closeCount = 0;
                $notWithinQuotes = false;
                foreach ($temp as &$value) {
                    
                    $notWithinQuotes = $notWithinQuotes === false;
                    if ($notWithinQuotes === true) {
                        $openCount += substr_count($value, self::FORMULA_OPEN_MATRIX_BRACE);
                        $closeCount += substr_count($value, self::FORMULA_CLOSE_MATRIX_BRACE);
                        $value = str_replace($matrixReplaceFrom, $matrixReplaceTo, $value);
                    }
                }
                unset($value);
                
                $formula = implode(self::FORMULA_STRING_QUOTE, $temp);
            } else {
                
                $openCount = substr_count($formula, self::FORMULA_OPEN_MATRIX_BRACE);
                $closeCount = substr_count($formula, self::FORMULA_CLOSE_MATRIX_BRACE);
                $formula = str_replace($matrixReplaceFrom, $matrixReplaceTo, $formula);
            }
            
            if ($openCount < $closeCount) {
                if ($openCount > 0) {
                    return $this->raiseFormulaError("Formula Error: Mismatched matrix braces '}'");
                }

                return $this->raiseFormulaError("Formula Error: Unexpected '}' encountered");
            } elseif ($openCount > $closeCount) {
                if ($closeCount > 0) {
                    return $this->raiseFormulaError("Formula Error: Mismatched matrix braces '{'");
                }

                return $this->raiseFormulaError("Formula Error: Unexpected '{' encountered");
            }
        }

        return $formula;
    }

    
    private const COMPARISON_OPERATORS = ['>' => true, '<' => true, '=' => true, '>=' => true, '<=' => true, '<>' => true];

    
    private const OPERATOR_PRECEDENCE = [
        ':' => 9, 
        '∩' => 8, 
        '∪' => 7, 
        '~' => 6, 
        '%' => 5, 
        '^' => 4, 
        '*' => 3, '/' => 3, 
        '+' => 2, '-' => 2, 
        '&' => 1, 
        '>' => 0, '<' => 0, '=' => 0, '>=' => 0, '<=' => 0, '<>' => 0, 
    ];

    
    private function internalParseFormula(string $formula, ?Cell $cell = null): bool|array
    {
        if (($formula = $this->convertMatrixReferences(trim($formula))) === false) {
            return false;
        }
        $phpSpreadsheetFunctions = &self::getFunctionsAddress();

        
        
        $pCellParent = ($cell !== null) ? $cell->getWorksheet() : null;

        $regexpMatchString = '/^((?<string>' . self::CALCULATION_REGEXP_STRING
                                . ')|(?<function>' . self::CALCULATION_REGEXP_FUNCTION
                                . ')|(?<cellRef>' . self::CALCULATION_REGEXP_CELLREF
                                . ')|(?<colRange>' . self::CALCULATION_REGEXP_COLUMN_RANGE
                                . ')|(?<rowRange>' . self::CALCULATION_REGEXP_ROW_RANGE
                                . ')|(?<number>' . self::CALCULATION_REGEXP_NUMBER
                                . ')|(?<openBrace>' . self::CALCULATION_REGEXP_OPENBRACE
                                . ')|(?<structuredReference>' . self::CALCULATION_REGEXP_STRUCTURED_REFERENCE
                                . ')|(?<definedName>' . self::CALCULATION_REGEXP_DEFINEDNAME
                                . ')|(?<error>' . self::CALCULATION_REGEXP_ERROR
                                . '))/sui';

        
        $index = 0;
        $stack = new Stack($this->branchPruner);
        $output = [];
        $expectingOperator = false; 
        
        $expectingOperand = false; 
        

        
        
        while (true) {
            
            
            $this->branchPruner->initialiseForLoop();

            $opCharacter = $formula[$index]; 

            
            if ((isset(self::COMPARISON_OPERATORS[$opCharacter])) && (strlen($formula) > $index) && isset($formula[$index + 1], self::COMPARISON_OPERATORS[$formula[$index + 1]])) {
                $opCharacter .= $formula[++$index];
            }
            
            
            $isOperandOrFunction = (bool) preg_match($regexpMatchString, substr($formula, $index), $match);

            $expectingOperatorCopy = $expectingOperator;
            if ($opCharacter === '-' && !$expectingOperator) {                
                
                $stack->push('Unary Operator', '~');
                ++$index; 
            } elseif ($opCharacter === '%' && $expectingOperator) {
                
                $stack->push('Unary Operator', '%');
                ++$index;
            } elseif ($opCharacter === '+' && !$expectingOperator) {            
                ++$index; 
            } elseif ((($opCharacter === '~') || ($opCharacter === '∩') || ($opCharacter === '∪')) && (!$isOperandOrFunction)) {
                
                return $this->raiseFormulaError("Formula Error: Illegal character '~'"); 
            } elseif ((isset(self::CALCULATION_OPERATORS[$opCharacter]) || $isOperandOrFunction) && $expectingOperator) {    
                while (self::swapOperands($stack, $opCharacter)) {
                    $output[] = $stack->pop(); 
                }

                
                $stack->push('Binary Operator', $opCharacter);

                ++$index;
                $expectingOperator = false;
            } elseif ($opCharacter === ')' && $expectingOperator) { 
                $expectingOperand = false;
                while (($o2 = $stack->pop()) && $o2['value'] !== '(') { 
                    $output[] = $o2;
                }
                $d = $stack->last(2);

                
                
                $this->branchPruner->decrementDepth();

                if (is_array($d) && preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/miu', $d['value'], $matches)) {
                    
                    try {
                        $this->branchPruner->closingBrace($d['value']);
                    } catch (Exception $e) {
                        return $this->raiseFormulaError($e->getMessage(), $e->getCode(), $e);
                    }

                    $functionName = $matches[1]; 
                    $d = $stack->pop();
                    $argumentCount = $d['value'] ?? 0; 
                    $output[] = $d; 
                    $output[] = $stack->pop(); 
                    if (isset(self::$controlFunctions[$functionName])) {
                        $expectedArgumentCount = self::$controlFunctions[$functionName]['argumentCount'];
                    } elseif (isset($phpSpreadsheetFunctions[$functionName])) {
                        $expectedArgumentCount = $phpSpreadsheetFunctions[$functionName]['argumentCount'];
                    } else {    
                        return $this->raiseFormulaError('Formula Error: Internal error, non-function on stack');
                    }
                    
                    $argumentCountError = false;
                    $expectedArgumentCountString = null;
                    if (is_numeric($expectedArgumentCount)) {
                        if ($expectedArgumentCount < 0) {
                            if ($argumentCount > abs($expectedArgumentCount + 0)) {
                                $argumentCountError = true;
                                $expectedArgumentCountString = 'no more than ' . abs($expectedArgumentCount + 0);
                            }
                        } else {
                            if ($argumentCount != $expectedArgumentCount) {
                                $argumentCountError = true;
                                $expectedArgumentCountString = $expectedArgumentCount;
                            }
                        }
                    } elseif (is_string($expectedArgumentCount) && $expectedArgumentCount !== '*') {
                        if (1 !== preg_match('/(\d*)([-+,])(\d*)/', $expectedArgumentCount, $argMatch)) {
                            $argMatch = ['', '', '', ''];
                        }
                        switch ($argMatch[2]) {
                            case '+':
                                if ($argumentCount < $argMatch[1]) {
                                    $argumentCountError = true;
                                    $expectedArgumentCountString = $argMatch[1] . ' or more ';
                                }

                                break;
                            case '-':
                                if (($argumentCount < $argMatch[1]) || ($argumentCount > $argMatch[3])) {
                                    $argumentCountError = true;
                                    $expectedArgumentCountString = 'between ' . $argMatch[1] . ' and ' . $argMatch[3];
                                }

                                break;
                            case ',':
                                if (($argumentCount != $argMatch[1]) && ($argumentCount != $argMatch[3])) {
                                    $argumentCountError = true;
                                    $expectedArgumentCountString = 'either ' . $argMatch[1] . ' or ' . $argMatch[3];
                                }

                                break;
                        }
                    }
                    if ($argumentCountError) {
                        return $this->raiseFormulaError("Formula Error: Wrong number of arguments for $functionName() function: $argumentCount given, " . $expectedArgumentCountString . ' expected');
                    }
                }
                ++$index;
            } elseif ($opCharacter === ',') { 
                try {
                    $this->branchPruner->argumentSeparator();
                } catch (Exception $e) {
                    return $this->raiseFormulaError($e->getMessage(), $e->getCode(), $e);
                }

                while (($o2 = $stack->pop()) && $o2['value'] !== '(') {        
                    $output[] = $o2; 
                }
                
                
                if (($expectingOperand) || (!$expectingOperator)) {
                    $output[] = $stack->getStackItem('Empty Argument', null, 'NULL');
                }
                
                $d = $stack->last(2);
                if (!preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/miu', $d['value'] ?? '', $matches)) {
                    
                    
                    
                    
                    return $this->raiseFormulaError('Formula Error: Unexpected ,');
                }

                
                $d = $stack->pop();
                ++$d['value']; 

                $stack->pushStackItem($d);
                $stack->push('Brace', '('); 

                $expectingOperator = false;
                $expectingOperand = true;
                ++$index;
            } elseif ($opCharacter === '(' && !$expectingOperator) {
                
                $this->branchPruner->incrementDepth();
                $stack->push('Brace', '(', null);
                ++$index;
            } elseif ($isOperandOrFunction && !$expectingOperatorCopy) {
                
                $expectingOperator = true;
                $expectingOperand = false;
                $val = $match[1] ?? ''; /
                            $startRowColRef = $output[count($output) - 1]['value'] ?? '';
                            [$rangeWS1, $startRowColRef] = Worksheet::extractSheetTitle($startRowColRef, true);
                            $rangeSheetRef = $rangeWS1;
                            if ($rangeWS1 !== '') {
                                $rangeWS1 .= '!';
                            }
                            if (str_starts_with($rangeSheetRef, "'")) {
                                $rangeSheetRef = Worksheet::unApostrophizeTitle($rangeSheetRef);
                            }
                            [$rangeWS2, $val] = Worksheet::extractSheetTitle($val, true);
                            if ($rangeWS2 !== '') {
                                $rangeWS2 .= '!';
                            } else {
                                $rangeWS2 = $rangeWS1;
                            }

                            $refSheet = $pCellParent;
                            if ($pCellParent !== null && $rangeSheetRef !== '' && $rangeSheetRef !== $pCellParent->getTitle()) {
                                $refSheet = $pCellParent->getParentOrThrow()->getSheetByName($rangeSheetRef);
                            }

                            if (ctype_digit($val) && $val <= 1048576) {
                                
                                $stackItemType = 'Row Reference';
                                $valx = $val;
                                $endRowColRef = ($refSheet !== null) ? $refSheet->getHighestDataColumn($valx) : AddressRange::MAX_COLUMN; 
                                $val = "{$rangeWS2}{$endRowColRef}{$val}";
                            } elseif (ctype_alpha($val) && strlen($val) <= 3) {
                                
                                $stackItemType = 'Column Reference';
                                $endRowColRef = ($refSheet !== null) ? $refSheet->getHighestDataRow($val) : AddressRange::MAX_ROW; 
                                $val = "{$rangeWS2}{$val}{$endRowColRef}";
                            }
                            $stackItemReference = $val;
                        }
                    } elseif ($opCharacter === self::FORMULA_STRING_QUOTE) {
                        
                        $val = self::wrapResult(str_replace('""', self::FORMULA_STRING_QUOTE, StringHelper::convertToString(self::unwrapResult($val))));
                    } elseif (isset(self::EXCEL_CONSTANTS[trim(strtoupper($val))])) {
                        $stackItemType = 'Constant';
                        $excelConstant = trim(strtoupper($val));
                        $val = self::EXCEL_CONSTANTS[$excelConstant];
                        $stackItemReference = $excelConstant;
                    } elseif (($localeConstant = array_search(trim(strtoupper($val)), self::$localeBoolean)) !== false) {
                        $stackItemType = 'Constant';
                        $val = self::EXCEL_CONSTANTS[$localeConstant];
                        $stackItemReference = $localeConstant;
                    } elseif (
                        preg_match('/^' . self::CALCULATION_REGEXP_ROW_RANGE . '/miu', substr($formula, $index), $rowRangeReference)
                    ) {
                        $val = $rowRangeReference[1];
                        $length = strlen($rowRangeReference[1]);
                        $stackItemType = 'Row Reference';
                        
                        $val = str_replace(["''", '""'], ["'", '"'], $val);
                        $column = 'A';
                        if (($testPrevOp !== null && $testPrevOp['value'] === ':') && $pCellParent !== null) {
                            $column = $pCellParent->getHighestDataColumn($val);
                        }
                        $val = "{$rowRangeReference[2]}{$column}{$rowRangeReference[7]}";
                        $stackItemReference = $val;
                    } elseif (
                        preg_match('/^' . self::CALCULATION_REGEXP_COLUMN_RANGE . '/miu', substr($formula, $index), $columnRangeReference)
                    ) {
                        $val = $columnRangeReference[1];
                        $length = strlen($val);
                        $stackItemType = 'Column Reference';
                        
                        $val = str_replace(["''", '""'], ["'", '"'], $val);
                        $row = '1';
                        if (($testPrevOp !== null && $testPrevOp['value'] === ':') && $pCellParent !== null) {
                            $row = $pCellParent->getHighestDataRow($val);
                        }
                        $val = "{$val}{$row}";
                        $stackItemReference = $val;
                    } elseif (preg_match('/^' . self::CALCULATION_REGEXP_DEFINEDNAME . '.*/miu', $val, $match)) {
                        $stackItemType = 'Defined Name';
                        $stackItemReference = $val;
                    } elseif (is_numeric($val)) {
                        if ((str_contains((string) $val, '.')) || (stripos((string) $val, 'e') !== false) || ($val > PHP_INT_MAX) || ($val < -PHP_INT_MAX)) {
                            $val = (float) $val;
                        } else {
                            $val = (int) $val;
                        }
                    }

                    $details = $stack->getStackItem($stackItemType, $val, $stackItemReference);
                    if ($localeConstant) {
                        $details['localeValue'] = $localeConstant;
                    }
                    $output[] = $details;
                }
                $index += $length;
            } elseif ($opCharacter === '$') { 
                ++$index;
            } elseif ($opCharacter === ')') { 
                if ($expectingOperand) {
                    $output[] = $stack->getStackItem('Empty Argument', null, 'NULL');
                    $expectingOperand = false;
                    $expectingOperator = true;
                } else {
                    return $this->raiseFormulaError("Formula Error: Unexpected ')'");
                }
            } elseif (isset(self::CALCULATION_OPERATORS[$opCharacter]) && !$expectingOperator) {
                return $this->raiseFormulaError("Formula Error: Unexpected operator '$opCharacter'");
            } else {    
                return $this->raiseFormulaError('Formula Error: An unexpected error occurred');
            }
            
            if ($index == strlen($formula)) {
                
                
                if ((isset(self::CALCULATION_OPERATORS[$opCharacter])) && ($opCharacter != '%')) {
                    return $this->raiseFormulaError("Formula Error: Operator '$opCharacter' has no operands");
                }

                break;
            }
            
            while (($formula[$index] === "\n") || ($formula[$index] === "\r")) {
                ++$index;
            }

            if ($formula[$index] === ' ') {
                while ($formula[$index] === ' ') {
                    ++$index;
                }

                
                
                $countOutputMinus1 = count($output) - 1;
                if (
                    ($expectingOperator)
                    && array_key_exists($countOutputMinus1, $output)
                    && is_array($output[$countOutputMinus1])
                    && array_key_exists('type', $output[$countOutputMinus1])
                    && (
                        (preg_match('/^' . self::CALCULATION_REGEXP_CELLREF . '.*/miu', substr($formula, $index), $match))
                            && ($output[$countOutputMinus1]['type'] === 'Cell Reference')
                        || (preg_match('/^' . self::CALCULATION_REGEXP_DEFINEDNAME . '.*/miu', substr($formula, $index), $match))
                            && ($output[$countOutputMinus1]['type'] === 'Defined Name' || $output[$countOutputMinus1]['type'] === 'Value')
                        || (preg_match('/^' . self::CALCULATION_REGEXP_STRUCTURED_REFERENCE . '.*/miu', substr($formula, $index), $match))
                            && ($output[$countOutputMinus1]['type'] === Operands\StructuredReference::NAME || $output[$countOutputMinus1]['type'] === 'Value')
                    )
                ) {
                    while (self::swapOperands($stack, $opCharacter)) {
                        $output[] = $stack->pop(); 
                    }
                    $stack->push('Binary Operator', '∩'); 
                    $expectingOperator = false;
                }
            }
        }

        while (($op = $stack->pop()) !== null) {
            
            if ($op['value'] == '(') {
                return $this->raiseFormulaError("Formula Error: Expecting ')'"); 
            }
            $output[] = $op;
        }

        return $output;
    }

    
    private static function dataTestReference(array &$operandData): mixed
    {
        $operand = $operandData['value'];
        if (($operandData['reference'] === null) && (is_array($operand))) {
            $rKeys = array_keys($operand);
            $rowKey = array_shift($rKeys);
            if (is_array($operand[$rowKey]) === false) {
                $operandData['value'] = $operand[$rowKey];

                return $operand[$rowKey];
            }

            $cKeys = array_keys(array_keys($operand[$rowKey]));
            $colKey = array_shift($cKeys);
            if (ctype_upper("$colKey")) {
                $operandData['reference'] = $colKey . $rowKey;
            }
        }

        return $operand;
    }

    private static int $matchIndex8 = 8;

    private static int $matchIndex9 = 9;

    private static int $matchIndex10 = 10;

    
    private function processTokenStack(false|array $tokens, ?string $cellID = null, ?Cell $cell = null)
    {
        if ($tokens === false) {
            return false;
        }
        $phpSpreadsheetFunctions = &self::getFunctionsAddress();

        
        
        $pCellWorksheet = ($cell !== null) ? $cell->getWorksheet() : null;
        $originalCoordinate = $cell?->getCoordinate();
        $pCellParent = ($cell !== null) ? $cell->getParent() : null;
        $stack = new Stack($this->branchPruner);

        
        $fakedForBranchPruning = [];
        
        $branchStore = [];
        
        foreach ($tokens as $tokenIdx => $tokenData) {
            $this->processingAnchorArray = false;
            if ($tokenData['type'] === 'Cell Reference' && isset($tokens[$tokenIdx + 1]) && $tokens[$tokenIdx + 1]['type'] === 'Operand Count for Function ANCHORARRAY()') {
                $this->processingAnchorArray = true;
            }
            $token = $tokenData['value'];
            
            $storeKey = $tokenData['storeKey'] ?? null;
            if ($this->branchPruningEnabled && isset($tokenData['onlyIf'])) {
                $onlyIfStoreKey = $tokenData['onlyIf'];
                $storeValue = $branchStore[$onlyIfStoreKey] ?? null;
                $storeValueAsBool = ($storeValue === null)
                    ? true : (bool) Functions::flattenSingleValue($storeValue);
                if (is_array($storeValue)) {
                    $wrappedItem = end($storeValue);
                    $storeValue = is_array($wrappedItem) ? end($wrappedItem) : $wrappedItem;
                }

                if (
                    (isset($storeValue) || $tokenData['reference'] === 'NULL')
                    && (!$storeValueAsBool || Information\ErrorValue::isError($storeValue) || ($storeValue === 'Pruned branch'))
                ) {
                    
                    if (!isset($fakedForBranchPruning['onlyIf-' . $onlyIfStoreKey])) {
                        $stack->push('Value', 'Pruned branch (only if ' . $onlyIfStoreKey . ') ' . $token);
                        $fakedForBranchPruning['onlyIf-' . $onlyIfStoreKey] = true;
                    }

                    if (isset($storeKey)) {
                        
                        
                        $branchStore[$storeKey] = 'Pruned branch';
                        $fakedForBranchPruning['onlyIfNot-' . $storeKey] = true;
                        $fakedForBranchPruning['onlyIf-' . $storeKey] = true;
                    }

                    continue;
                }
            }

            if ($this->branchPruningEnabled && isset($tokenData['onlyIfNot'])) {
                $onlyIfNotStoreKey = $tokenData['onlyIfNot'];
                $storeValue = $branchStore[$onlyIfNotStoreKey] ?? null;
                $storeValueAsBool = ($storeValue === null)
                    ? true : (bool) Functions::flattenSingleValue($storeValue);
                if (is_array($storeValue)) {
                    $wrappedItem = end($storeValue);
                    $storeValue = is_array($wrappedItem) ? end($wrappedItem) : $wrappedItem;
                }

                if (
                    (isset($storeValue) || $tokenData['reference'] === 'NULL')
                    && ($storeValueAsBool || Information\ErrorValue::isError($storeValue) || ($storeValue === 'Pruned branch'))
                ) {
                    
                    if (!isset($fakedForBranchPruning['onlyIfNot-' . $onlyIfNotStoreKey])) {
                        $stack->push('Value', 'Pruned branch (only if not ' . $onlyIfNotStoreKey . ') ' . $token);
                        $fakedForBranchPruning['onlyIfNot-' . $onlyIfNotStoreKey] = true;
                    }

                    if (isset($storeKey)) {
                        
                        
                        $branchStore[$storeKey] = 'Pruned branch';
                        $fakedForBranchPruning['onlyIfNot-' . $storeKey] = true;
                        $fakedForBranchPruning['onlyIf-' . $storeKey] = true;
                    }

                    continue;
                }
            }

            if ($token instanceof Operands\StructuredReference) {
                if ($cell === null) {
                    return $this->raiseFormulaError('Structured References must exist in a Cell context');
                }

                try {
                    $cellRange = $token->parse($cell);
                    if (str_contains($cellRange, ':')) {
                        $this->debugLog->writeDebugLog('Evaluating Structured Reference %s as Cell Range %s', $token->value(), $cellRange);
                        $rangeValue = self::getInstance($cell->getWorksheet()->getParent())->_calculateFormulaValue("={$cellRange}", $cellRange, $cell);
                        $stack->push('Value', $rangeValue);
                        $this->debugLog->writeDebugLog('Evaluated Structured Reference %s as value %s', $token->value(), $this->showValue($rangeValue));
                    } else {
                        $this->debugLog->writeDebugLog('Evaluating Structured Reference %s as Cell %s', $token->value(), $cellRange);
                        $cellValue = $cell->getWorksheet()->getCell($cellRange)->getCalculatedValue(false);
                        $stack->push('Cell Reference', $cellValue, $cellRange);
                        $this->debugLog->writeDebugLog('Evaluated Structured Reference %s as value %s', $token->value(), $this->showValue($cellValue));
                    }
                } catch (Exception $e) {
                    if ($e->getCode() === Exception::CALCULATION_ENGINE_PUSH_TO_STACK) {
                        $stack->push('Error', ExcelError::REF(), null);
                        $this->debugLog->writeDebugLog('Evaluated Structured Reference %s as error value %s', $token->value(), ExcelError::REF());
                    } else {
                        return $this->raiseFormulaError($e->getMessage(), $e->getCode(), $e);
                    }
                }
            } elseif (!is_numeric($token) && !is_object($token) && isset(self::BINARY_OPERATORS[$token])) {
                
                
                $operand2Data = $stack->pop();
                if ($operand2Data === null) {
                    return $this->raiseFormulaError('Internal error - Operand value missing from stack');
                }
                $operand1Data = $stack->pop();
                if ($operand1Data === null) {
                    return $this->raiseFormulaError('Internal error - Operand value missing from stack');
                }

                $operand1 = self::dataTestReference($operand1Data);
                $operand2 = self::dataTestReference($operand2Data);

                
                if ($token == ':') {
                    $this->debugLog->writeDebugLog('Evaluating Range %s %s %s', $this->showValue($operand1Data['reference']), $token, $this->showValue($operand2Data['reference']));
                } else {
                    $this->debugLog->writeDebugLog('Evaluating %s %s %s', $this->showValue($operand1), $token, $this->showValue($operand2));
                }

                
                switch ($token) {
                    
                    case '>': 
                    case '<': 
                    case '>=': 
                    case '<=': 
                    case '=': 
                    case '<>': 
                        $result = $this->executeBinaryComparisonOperation($operand1, $operand2, (string) $token, $stack);
                        if (isset($storeKey)) {
                            $branchStore[$storeKey] = $result;
                        }

                        break;
                    
                    case ':': 
                        if ($operand1Data['type'] === 'Defined Name') {
                            if (preg_match('/$' . self::CALCULATION_REGEXP_DEFINEDNAME . '^/mui', $operand1Data['reference']) !== false && $this->spreadsheet !== null) {
                                $definedName = $this->spreadsheet->getNamedRange($operand1Data['reference']);
                                if ($definedName !== null) {
                                    $operand1Data['reference'] = $operand1Data['value'] = str_replace('$', '', $definedName->getValue());
                                }
                            }
                        }
                        if (str_contains($operand1Data['reference'] ?? '', '!')) {
                            [$sheet1, $operand1Data['reference']] = Worksheet::extractSheetTitle($operand1Data['reference'], true, true);
                        } else {
                            $sheet1 = ($pCellWorksheet !== null) ? $pCellWorksheet->getTitle() : '';
                        }
                        $sheet1 ??= '';

                        
                        $op2ref = $operand2Data['reference'];
                        [$sheet2, $operand2Data['reference']] = Worksheet::extractSheetTitle($op2ref, true, true);
                        if (empty($sheet2)) {
                            $sheet2 = $sheet1;
                        }

                        if ($sheet1 === $sheet2) {
                            if ($operand1Data['reference'] === null && $cell !== null) {
                                if (is_array($operand1Data['value'])) {
                                    $operand1Data['reference'] = $cell->getCoordinate();
                                } elseif ((trim($operand1Data['value']) != '') && (is_numeric($operand1Data['value']))) {
                                    $operand1Data['reference'] = $cell->getColumn() . $operand1Data['value'];
                                } elseif (trim($operand1Data['value']) == '') {
                                    $operand1Data['reference'] = $cell->getCoordinate();
                                } else {
                                    $operand1Data['reference'] = $operand1Data['value'] . $cell->getRow();
                                }
                            }
                            if ($operand2Data['reference'] === null && $cell !== null) {
                                if (is_array($operand2Data['value'])) {
                                    $operand2Data['reference'] = $cell->getCoordinate();
                                } elseif ((trim($operand2Data['value']) != '') && (is_numeric($operand2Data['value']))) {
                                    $operand2Data['reference'] = $cell->getColumn() . $operand2Data['value'];
                                } elseif (trim($operand2Data['value']) == '') {
                                    $operand2Data['reference'] = $cell->getCoordinate();
                                } else {
                                    $operand2Data['reference'] = $operand2Data['value'] . $cell->getRow();
                                }
                            }

                            $oData = array_merge(explode(':', $operand1Data['reference'] ?? ''), explode(':', $operand2Data['reference'] ?? ''));
                            $oCol = $oRow = [];
                            $breakNeeded = false;
                            foreach ($oData as $oDatum) {
                                try {
                                    $oCR = Coordinate::coordinateFromString($oDatum);
                                    $oCol[] = Coordinate::columnIndexFromString($oCR[0]) - 1;
                                    $oRow[] = $oCR[1];
                                } catch (\Exception) {
                                    $stack->push('Error', ExcelError::REF(), null);
                                    $breakNeeded = true;

                                    break;
                                }
                            }
                            if ($breakNeeded) {
                                break;
                            }
                            $cellRef = Coordinate::stringFromColumnIndex(min($oCol) + 1) . min($oRow) . ':' . Coordinate::stringFromColumnIndex(max($oCol) + 1) . max($oRow); 
                            if ($pCellParent !== null && $this->spreadsheet !== null) {
                                $cellValue = $this->extractCellRange($cellRef, $this->spreadsheet->getSheetByName($sheet1), false);
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }

                            $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($cellValue));
                            $stack->push('Cell Reference', $cellValue, $cellRef);
                        } else {
                            $this->debugLog->writeDebugLog('Evaluation Result is a 
                            $stack->push('Error', ExcelError::REF(), null);
                        }

                        break;
                    case '+':            
                    case '-':            
                    case '*':            
                    case '/':            
                    case '^':            
                        $result = $this->executeNumericBinaryOperation($operand1, $operand2, $token, $stack);
                        if (isset($storeKey)) {
                            $branchStore[$storeKey] = $result;
                        }

                        break;
                    case '&':            
                        
                        
                        
                        $operand1 = self::boolToString($operand1);
                        $operand2 = self::boolToString($operand2);
                        if (is_array($operand1) || is_array($operand2)) {
                            if (is_string($operand1)) {
                                $operand1 = self::unwrapResult($operand1);
                            }
                            if (is_string($operand2)) {
                                $operand2 = self::unwrapResult($operand2);
                            }
                            
                            [$rows, $columns] = self::checkMatrixOperands($operand1, $operand2, 2);

                            for ($row = 0; $row < $rows; ++$row) {
                                for ($column = 0; $column < $columns; ++$column) {
                                    $op1x = self::boolToString($operand1[$row][$column]);
                                    $op2x = self::boolToString($operand2[$row][$column]);
                                    if (Information\ErrorValue::isError($op1x)) {
                                        
                                    } elseif (Information\ErrorValue::isError($op2x)) {
                                        $operand1[$row][$column] = $op2x;
                                    } else {
                                        
                                        
                                        $operand1[$row][$column]
                                            = StringHelper::substring(
                                                $op1x . $op2x,
                                                0,
                                                DataType::MAX_STRING_LENGTH
                                            );
                                    }
                                }
                            }
                            $result = $operand1;
                        } else {
                            if (Information\ErrorValue::isError($operand1)) {
                                $result = $operand1;
                            } elseif (Information\ErrorValue::isError($operand2)) {
                                $result = $operand2;
                            } else {
                                $result = str_replace('""', self::FORMULA_STRING_QUOTE, self::unwrapResult($operand1) . self::unwrapResult($operand2)); /
                        
                        $rowIntersect = array_intersect_key($operand1, $operand2);
                        $cellIntersect = $oCol = $oRow = [];
                        foreach (array_keys($rowIntersect) as $row) {
                            $oRow[] = $row;
                            foreach ($rowIntersect[$row] as $col => $data) {
                                $oCol[] = Coordinate::columnIndexFromString($col) - 1;
                                $cellIntersect[$row] = array_intersect_key($operand1[$row], $operand2[$row]);
                            }
                        }
                        if (count(Functions::flattenArray($cellIntersect)) === 0) {
                            $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($cellIntersect));
                            $stack->push('Error', ExcelError::null(), null);
                        } else {
                            $cellRef = Coordinate::stringFromColumnIndex(min($oCol) + 1) . min($oRow) . ':' 
                                . Coordinate::stringFromColumnIndex(max($oCol) + 1) . max($oRow); 
                            $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($cellIntersect));
                            $stack->push('Value', $cellIntersect, $cellRef);
                        }

                        break;
                }
            } elseif (($token === '~') || ($token === '%')) {
                
                if (($arg = $stack->pop()) === null) {
                    return $this->raiseFormulaError('Internal error - Operand value missing from stack');
                }
                $arg = $arg['value'];
                if ($token === '~') {
                    $this->debugLog->writeDebugLog('Evaluating Negation of %s', $this->showValue($arg));
                    $multiplier = -1;
                } else {
                    $this->debugLog->writeDebugLog('Evaluating Percentile of %s', $this->showValue($arg));
                    $multiplier = 0.01;
                }
                if (is_array($arg)) {
                    $operand2 = $multiplier;
                    $result = $arg;
                    [$rows, $columns] = self::checkMatrixOperands($result, $operand2, 0);
                    for ($row = 0; $row < $rows; ++$row) {
                        for ($column = 0; $column < $columns; ++$column) {
                            if (self::isNumericOrBool($result[$row][$column])) {
                                $result[$row][$column] *= $multiplier;
                            } else {
                                $result[$row][$column] = self::makeError($result[$row][$column]);
                            }
                        }
                    }

                    $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($result));
                    $stack->push('Value', $result);
                    if (isset($storeKey)) {
                        $branchStore[$storeKey] = $result;
                    }
                } else {
                    $this->executeNumericBinaryOperation($multiplier, $arg, '*', $stack);
                }
            } elseif (preg_match('/^' . self::CALCULATION_REGEXP_CELLREF . '$/i', $token ?? '', $matches)) {
                $cellRef = null;

                
                if (isset($matches[self::$matchIndex8])) {
                    if ($cell === null) {
                        
                        $cellValue = ExcelError::REF();
                    } else {
                        $cellRef = $matches[6] . $matches[7] . ':' . $matches[self::$matchIndex9] . $matches[self::$matchIndex10];
                        if ($matches[2] > '') {
                            $matches[2] = trim($matches[2], "\"'");
                            if ((str_contains($matches[2], '[')) || (str_contains($matches[2], ']'))) {
                                
                                return $this->raiseFormulaError('Unable to access External Workbook');
                            }
                            $matches[2] = trim($matches[2], "\"'");
                            $this->debugLog->writeDebugLog('Evaluating Cell Range %s in worksheet %s', $cellRef, $matches[2]);
                            if ($pCellParent !== null && $this->spreadsheet !== null) {
                                $cellValue = $this->extractCellRange($cellRef, $this->spreadsheet->getSheetByName($matches[2]), false);
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $this->debugLog->writeDebugLog('Evaluation Result for cells %s in worksheet %s is %s', $cellRef, $matches[2], $this->showTypeDetails($cellValue));
                        } else {
                            $this->debugLog->writeDebugLog('Evaluating Cell Range %s in current worksheet', $cellRef);
                            if ($pCellParent !== null) {
                                $cellValue = $this->extractCellRange($cellRef, $pCellWorksheet, false);
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $this->debugLog->writeDebugLog('Evaluation Result for cells %s is %s', $cellRef, $this->showTypeDetails($cellValue));
                        }
                    }
                } else {
                    if ($cell === null) {
                        
                        $cellValue = ExcelError::REF();
                    } else {
                        $cellRef = $matches[6] . $matches[7];
                        if ($matches[2] > '') {
                            $matches[2] = trim($matches[2], "\"'");
                            if ((str_contains($matches[2], '[')) || (str_contains($matches[2], ']'))) {
                                
                                return $this->raiseFormulaError('Unable to access External Workbook');
                            }
                            $this->debugLog->writeDebugLog('Evaluating Cell %s in worksheet %s', $cellRef, $matches[2]);
                            if ($pCellParent !== null && $this->spreadsheet !== null) {
                                $cellSheet = $this->spreadsheet->getSheetByName($matches[2]);
                                if ($cellSheet && $cellSheet->cellExists($cellRef)) {
                                    $cellValue = $this->extractCellRange($cellRef, $this->spreadsheet->getSheetByName($matches[2]), false);
                                    $cell->attach($pCellParent);
                                } else {
                                    $cellRef = ($cellSheet !== null) ? "'{$matches[2]}'!{$cellRef}" : $cellRef;
                                    $cellValue = ($cellSheet !== null) ? null : ExcelError::REF();
                                }
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $this->debugLog->writeDebugLog('Evaluation Result for cell %s in worksheet %s is %s', $cellRef, $matches[2], $this->showTypeDetails($cellValue));
                        } else {
                            $this->debugLog->writeDebugLog('Evaluating Cell %s in current worksheet', $cellRef);
                            if ($pCellParent !== null && $pCellParent->has($cellRef)) {
                                $cellValue = $this->extractCellRange($cellRef, $pCellWorksheet, false);
                                $cell->attach($pCellParent);
                            } else {
                                $cellValue = null;
                            }
                            $this->debugLog->writeDebugLog('Evaluation Result for cell %s is %s', $cellRef, $this->showTypeDetails($cellValue));
                        }
                    }
                }

                if ($this->getInstanceArrayReturnType() === self::RETURN_ARRAY_AS_ARRAY && !$this->processingAnchorArray && is_array($cellValue)) {
                    while (is_array($cellValue)) {
                        $cellValue = array_shift($cellValue);
                    }
                    if (is_string($cellValue)) {
                        $cellValue = preg_replace('/"/', '""', $cellValue);
                    }
                    $this->debugLog->writeDebugLog('Scalar Result for cell %s is %s', $cellRef, $this->showTypeDetails($cellValue));
                }
                $this->processingAnchorArray = false;
                $stack->push('Cell Value', $cellValue, $cellRef);
                if (isset($storeKey)) {
                    $branchStore[$storeKey] = $cellValue;
                }
            } elseif (preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/miu', $token ?? '', $matches)) {
                
                if ($cell !== null && $pCellParent !== null) {
                    $cell->attach($pCellParent);
                }

                $functionName = $matches[1];
                
                $argCount = $stack->pop();
                $argCount = $argCount['value'];
                if ($functionName !== 'MKMATRIX') {
                    $this->debugLog->writeDebugLog('Evaluating Function %s() with %s argument%s', self::localeFunc($functionName), (($argCount == 0) ? 'no' : $argCount), (($argCount == 1) ? '' : 's'));
                }
                if ((isset($phpSpreadsheetFunctions[$functionName])) || (isset(self::$controlFunctions[$functionName]))) {    
                    $passByReference = false;
                    $passCellReference = false;
                    $functionCall = null;
                    if (isset($phpSpreadsheetFunctions[$functionName])) {
                        $functionCall = $phpSpreadsheetFunctions[$functionName]['functionCall'];
                        $passByReference = isset($phpSpreadsheetFunctions[$functionName]['passByReference']);
                        $passCellReference = isset($phpSpreadsheetFunctions[$functionName]['passCellReference']);
                    } elseif (isset(self::$controlFunctions[$functionName])) {
                        $functionCall = self::$controlFunctions[$functionName]['functionCall'];
                        $passByReference = isset(self::$controlFunctions[$functionName]['passByReference']);
                        $passCellReference = isset(self::$controlFunctions[$functionName]['passCellReference']);
                    }

                    
                    $args = $argArrayVals = [];
                    $emptyArguments = [];
                    for ($i = 0; $i < $argCount; ++$i) {
                        $arg = $stack->pop();
                        $a = $argCount - $i - 1;
                        if (
                            ($passByReference)
                            && (isset($phpSpreadsheetFunctions[$functionName]['passByReference'][$a])) /
                            if ($arg['reference'] === null) {
                                $nextArg = $cellID;
                                if ($functionName === 'ISREF' && ($arg['type'] ?? '') === 'Value') {
                                    if (array_key_exists('value', $arg)) {
                                        $argValue = $arg['value'];
                                        if (is_scalar($argValue)) {
                                            $nextArg = $argValue;
                                        } elseif (empty($argValue)) {
                                            $nextArg = '';
                                        }
                                    }
                                }
                                $args[] = $nextArg;
                                if ($functionName !== 'MKMATRIX') {
                                    $argArrayVals[] = $this->showValue($cellID);
                                }
                            } else {
                                $args[] = $arg['reference'];
                                if ($functionName !== 'MKMATRIX') {
                                    $argArrayVals[] = $this->showValue($arg['reference']);
                                }
                            }
                        } else {
                            
                            if ($arg['type'] === 'Empty Argument' && in_array($functionName, ['MIN', 'MINA', 'MAX', 'MAXA', 'IF'], true)) {
                                $emptyArguments[] = false;
                                $args[] = $arg['value'] = 0;
                                $this->debugLog->writeDebugLog('Empty Argument reevaluated as 0');
                            } else {
                                $emptyArguments[] = $arg['type'] === 'Empty Argument';
                                $args[] = self::unwrapResult($arg['value']);
                            }
                            if ($functionName !== 'MKMATRIX') {
                                $argArrayVals[] = $this->showValue($arg['value']);
                            }
                        }
                    }

                    
                    krsort($args);
                    krsort($emptyArguments);

                    if ($argCount > 0 && is_array($functionCall)) {
                        
                        $functionCallCopy = $functionCall;
                        $args = $this->addDefaultArgumentValues($functionCallCopy, $args, $emptyArguments);
                    }

                    if (($passByReference) && ($argCount == 0)) {
                        $args[] = $cellID;
                        $argArrayVals[] = $this->showValue($cellID);
                    }

                    if ($functionName !== 'MKMATRIX') {
                        if ($this->debugLog->getWriteDebugLog()) {
                            krsort($argArrayVals);
                            $this->debugLog->writeDebugLog('Evaluating %s ( %s )', self::localeFunc($functionName), implode(self::$localeArgumentSeparator . ' ', Functions::flattenArray($argArrayVals)));
                        }
                    }

                    
                    if ($pCellWorksheet !== null && $originalCoordinate !== null) {
                        $pCellWorksheet->getCell($originalCoordinate);
                    }
                    
                    $args = $this->addCellReference($args, $passCellReference, $functionCall, $cell);

                    if (!is_array($functionCall)) {
                        foreach ($args as &$arg) {
                            $arg = Functions::flattenSingleValue($arg);
                        }
                        unset($arg);
                    }

                    
                    $result = call_user_func_array($functionCall, $args);

                    if ($functionName !== 'MKMATRIX') {
                        $this->debugLog->writeDebugLog('Evaluation Result for %s() function call is %s', self::localeFunc($functionName), $this->showTypeDetails($result));
                    }
                    $stack->push('Value', self::wrapResult($result));
                    if (isset($storeKey)) {
                        $branchStore[$storeKey] = $result;
                    }
                }
            } else {
                
                if (isset(self::EXCEL_CONSTANTS[strtoupper($token ?? '')])) {
                    $excelConstant = strtoupper($token);
                    $stack->push('Constant Value', self::EXCEL_CONSTANTS[$excelConstant]);
                    if (isset($storeKey)) {
                        $branchStore[$storeKey] = self::EXCEL_CONSTANTS[$excelConstant];
                    }
                    $this->debugLog->writeDebugLog('Evaluating Constant %s as %s', $excelConstant, $this->showTypeDetails(self::EXCEL_CONSTANTS[$excelConstant]));
                } elseif ((is_numeric($token)) || ($token === null) || (is_bool($token)) || ($token == '') || ($token[0] == self::FORMULA_STRING_QUOTE) || ($token[0] == '
                    $stack->push($tokenData['type'], $token, $tokenData['reference']);
                    if (isset($storeKey)) {
                        $branchStore[$storeKey] = $token;
                    }
                } elseif (preg_match('/^' . self::CALCULATION_REGEXP_DEFINEDNAME . '$/miu', $token, $matches)) {
                    
                    $definedName = $matches[6];
                    if (str_starts_with($definedName, '_xleta')) {
                        return Functions::NOT_YET_IMPLEMENTED;
                    }
                    if ($cell === null || $pCellWorksheet === null) {
                        return $this->raiseFormulaError("undefined name '$token'");
                    }
                    $specifiedWorksheet = trim($matches[2], "'");

                    $this->debugLog->writeDebugLog('Evaluating Defined Name %s', $definedName);
                    $namedRange = DefinedName::resolveName($definedName, $pCellWorksheet, $specifiedWorksheet);
                    
                    if ($namedRange === null && $this->spreadsheet !== null) {
                        $table = $this->spreadsheet->getTableByName($definedName);
                        if ($table !== null) {
                            $tableRange = Coordinate::getRangeBoundaries($table->getRange());
                            if ($table->getShowHeaderRow()) {
                                ++$tableRange[0][1];
                            }
                            if ($table->getShowTotalsRow()) {
                                --$tableRange[1][1];
                            }
                            $tableRangeString
                                = '$' . $tableRange[0][0]
                                . '$' . $tableRange[0][1]
                                . ':'
                                . '$' . $tableRange[1][0]
                                . '$' . $tableRange[1][1];
                            $namedRange = new NamedRange($definedName, $table->getWorksheet(), $tableRangeString);
                        }
                    }
                    if ($namedRange === null) {
                        return $this->raiseFormulaError("undefined name '$definedName'");
                    }

                    $result = $this->evaluateDefinedName($cell, $namedRange, $pCellWorksheet, $stack, $specifiedWorksheet !== '');

                    if (isset($storeKey)) {
                        $branchStore[$storeKey] = $result;
                    }
                } else {
                    return $this->raiseFormulaError("undefined name '$token'");
                }
            }
        }
        
        if ($stack->count() != 1) {
            return $this->raiseFormulaError('internal error');
        }
        
        $output = $stack->pop();
        $output = $output['value'];

        return $output;
    }

    private function validateBinaryOperand(mixed &$operand, Stack &$stack): bool
    {
        if (is_array($operand)) {
            if ((count($operand, COUNT_RECURSIVE) - count($operand)) == 1) {
                do {
                    $operand = array_pop($operand);
                } while (is_array($operand));
            }
        }
        
        if (is_string($operand)) {
            
            
            if ($operand > '' && $operand[0] == self::FORMULA_STRING_QUOTE) {
                $operand = StringHelper::convertToString(self::unwrapResult($operand));
            }
            
            if (!is_numeric($operand)) {
                
                if ($operand > '' && $operand[0] == '
                    $stack->push('Value', $operand);
                    $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($operand));

                    return false;
                } elseif (Engine\FormattedNumber::convertToNumberIfFormatted($operand) === false) {
                    
                    $stack->push('Error', '
                    $this->debugLog->writeDebugLog('Evaluation Result is a %s', $this->showTypeDetails('

                    return false;
                }
            }
        }

        
        return true;
    }

    
    private function executeArrayComparison(mixed $operand1, mixed $operand2, string $operation, Stack &$stack, bool $recursingArrays): array
    {
        $result = [];
        if (!is_array($operand2) && is_array($operand1)) {
            
            foreach ($operand1 as $x => $operandData) {
                $this->debugLog->writeDebugLog('Evaluating Comparison %s %s %s', $this->showValue($operandData), $operation, $this->showValue($operand2));
                $this->executeBinaryComparisonOperation($operandData, $operand2, $operation, $stack);
                
                $r = $stack->pop();
                $result[$x] = $r['value'];
            }
        } elseif (is_array($operand2) && !is_array($operand1)) {
            
            foreach ($operand2 as $x => $operandData) {
                $this->debugLog->writeDebugLog('Evaluating Comparison %s %s %s', $this->showValue($operand1), $operation, $this->showValue($operandData));
                $this->executeBinaryComparisonOperation($operand1, $operandData, $operation, $stack);
                
                $r = $stack->pop();
                $result[$x] = $r['value'];
            }
        } elseif (is_array($operand2) && is_array($operand1)) {
            
            if (!$recursingArrays) {
                self::checkMatrixOperands($operand1, $operand2, 2);
            }
            foreach ($operand1 as $x => $operandData) {
                $this->debugLog->writeDebugLog('Evaluating Comparison %s %s %s', $this->showValue($operandData), $operation, $this->showValue($operand2[$x]));
                $this->executeBinaryComparisonOperation($operandData, $operand2[$x], $operation, $stack, true);
                
                $r = $stack->pop();
                $result[$x] = $r['value'];
            }
        } else {
            throw new Exception('Neither operand is an arra');
        }
        
        $this->debugLog->writeDebugLog('Comparison Evaluation Result is %s', $this->showTypeDetails($result));
        
        $stack->push('Array', $result);

        return $result;
    }

    
    private function executeBinaryComparisonOperation(mixed $operand1, mixed $operand2, string $operation, Stack &$stack, bool $recursingArrays = false): array|bool
    {
        
        if ((is_array($operand1)) || (is_array($operand2))) {
            return $this->executeArrayComparison($operand1, $operand2, $operation, $stack, $recursingArrays);
        }

        $result = BinaryComparison::compare($operand1, $operand2, $operation);

        
        $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($result));
        
        $stack->push('Value', $result);

        return $result;
    }

    private function executeNumericBinaryOperation(mixed $operand1, mixed $operand2, string $operation, Stack &$stack): mixed
    {
        
        if (
            ($this->validateBinaryOperand($operand1, $stack) === false)
            || ($this->validateBinaryOperand($operand2, $stack) === false)
        ) {
            return false;
        }

        if (
            (Functions::getCompatibilityMode() != Functions::COMPATIBILITY_OPENOFFICE)
            && ((is_string($operand1) && !is_numeric($operand1) && $operand1 !== '')
                || (is_string($operand2) && !is_numeric($operand2) && $operand2 !== ''))
        ) {
            $result = ExcelError::VALUE();
        } elseif (is_array($operand1) || is_array($operand2)) {
            
            if (is_array($operand1)) {
                foreach ($operand1 as $key => $value) {
                    $operand1[$key] = Functions::flattenArray($value);
                }
            }
            if (is_array($operand2)) {
                foreach ($operand2 as $key => $value) {
                    $operand2[$key] = Functions::flattenArray($value);
                }
            }
            [$rows, $columns] = self::checkMatrixOperands($operand1, $operand2, 3);

            for ($row = 0; $row < $rows; ++$row) {
                for ($column = 0; $column < $columns; ++$column) {
                    if ($operand1[$row][$column] === null) {
                        $operand1[$row][$column] = 0;
                    } elseif (!self::isNumericOrBool($operand1[$row][$column])) {
                        $operand1[$row][$column] = self::makeError($operand1[$row][$column]);

                        continue;
                    }
                    if ($operand2[$row][$column] === null) {
                        $operand2[$row][$column] = 0;
                    } elseif (!self::isNumericOrBool($operand2[$row][$column])) {
                        $operand1[$row][$column] = self::makeError($operand2[$row][$column]);

                        continue;
                    }
                    
                    $operand1Val = $operand1[$row][$column];
                    
                    $operand2Val = $operand2[$row][$column];
                    switch ($operation) {
                        case '+':
                            $operand1[$row][$column] = $operand1Val + $operand2Val;

                            break;
                        case '-':
                            $operand1[$row][$column] = $operand1Val - $operand2Val;

                            break;
                        case '*':
                            $operand1[$row][$column] = $operand1Val * $operand2Val;

                            break;
                        case '/':
                            if ($operand2Val == 0) {
                                $operand1[$row][$column] = ExcelError::DIV0();
                            } else {
                                $operand1[$row][$column] = $operand1Val / $operand2Val;
                            }

                            break;
                        case '^':
                            $operand1[$row][$column] = $operand1Val ** $operand2Val;

                            break;

                        default:
                            throw new Exception('Unsupported numeric binary operation');
                    }
                }
            }
            $result = $operand1;
        } else {
            
            
            
            switch ($operation) {
                
                case '+':
                    $result = $operand1 + $operand2;

                    break;
                
                case '-':
                    $result = $operand1 - $operand2;

                    break;
                
                case '*':
                    $result = $operand1 * $operand2;

                    break;
                
                case '/':
                    if ($operand2 == 0) {
                        
                        $stack->push('Error', ExcelError::DIV0());
                        $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails(ExcelError::DIV0()));

                        return false;
                    }
                    $result = $operand1 / $operand2;

                    break;
                
                case '^':
                    $result = $operand1 ** $operand2;

                    break;

                default:
                    throw new Exception('Unsupported numeric binary operation');
            }
        }

        
        $this->debugLog->writeDebugLog('Evaluation Result is %s', $this->showTypeDetails($result));
        
        $stack->push('Value', $result);

        return $result;
    }

    
    protected function raiseFormulaError(string $errorMessage, int $code = 0, ?Throwable $exception = null): bool
    {
        $this->formulaError = $errorMessage;
        $this->cyclicReferenceStack->clear();
        $suppress = $this->suppressFormulaErrors;
        if (!$suppress) {
            throw new Exception($errorMessage, $code, $exception);
        }

        return false;
    }

    
    public function extractCellRange(string &$range = 'A1', ?Worksheet $worksheet = null, bool $resetLog = true): array
    {
        
        $returnValue = [];

        if ($worksheet !== null) {
            $worksheetName = $worksheet->getTitle();

            if (str_contains($range, '!')) {
                [$worksheetName, $range] = Worksheet::extractSheetTitle($range, true, true);
                $worksheet = ($this->spreadsheet === null) ? null : $this->spreadsheet->getSheetByName($worksheetName);
            }

            
            $aReferences = Coordinate::extractAllCellReferencesInRange($range);
            $range = "'" . $worksheetName . "'" . '!' . $range;
            $currentCol = '';
            $currentRow = 0;
            if (!isset($aReferences[1])) {
                
                sscanf($aReferences[0], '%[A-Z]%d', $currentCol, $currentRow);
                if ($worksheet !== null && $worksheet->cellExists($aReferences[0])) {
                    $temp = $worksheet->getCell($aReferences[0])->getCalculatedValue($resetLog);
                    if ($this->getInstanceArrayReturnType() === self::RETURN_ARRAY_AS_ARRAY) {
                        while (is_array($temp)) {
                            $temp = array_shift($temp);
                        }
                    }
                    $returnValue[$currentRow][$currentCol] = $temp;
                } else {
                    $returnValue[$currentRow][$currentCol] = null;
                }
            } else {
                
                foreach ($aReferences as $reference) {
                    
                    sscanf($reference, '%[A-Z]%d', $currentCol, $currentRow);
                    if ($worksheet !== null && $worksheet->cellExists($reference)) {
                        $temp = $worksheet->getCell($reference)->getCalculatedValue($resetLog);
                        if ($this->getInstanceArrayReturnType() === self::RETURN_ARRAY_AS_ARRAY) {
                            while (is_array($temp)) {
                                $temp = array_shift($temp);
                            }
                        }
                        $returnValue[$currentRow][$currentCol] = $temp;
                    } else {
                        $returnValue[$currentRow][$currentCol] = null;
                    }
                }
            }
        }

        return $returnValue;
    }

    
    public function extractNamedRange(string &$range = 'A1', ?Worksheet $worksheet = null, bool $resetLog = true): string|array
    {
        
        $returnValue = [];

        if ($worksheet !== null) {
            if (str_contains($range, '!')) {
                [$worksheetName, $range] = Worksheet::extractSheetTitle($range, true, true);
                $worksheet = ($this->spreadsheet === null) ? null : $this->spreadsheet->getSheetByName($worksheetName);
            }

            
            $namedRange = ($worksheet === null) ? null : DefinedName::resolveName($range, $worksheet);
            if ($namedRange === null) {
                return ExcelError::REF();
            }

            $worksheet = $namedRange->getWorksheet();
            $range = $namedRange->getValue();
            $splitRange = Coordinate::splitRange($range);
            
            if ($worksheet !== null && ctype_alpha($splitRange[0][0])) {
                $range = $splitRange[0][0] . '1:' . $splitRange[0][1] . $worksheet->getHighestRow();
            } elseif ($worksheet !== null && ctype_digit($splitRange[0][0])) {
                $range = 'A' . $splitRange[0][0] . ':' . $worksheet->getHighestColumn() . $splitRange[0][1];
            }

            
            $aReferences = Coordinate::extractAllCellReferencesInRange($range);
            if (!isset($aReferences[1])) {
                
                [$currentCol, $currentRow] = Coordinate::coordinateFromString($aReferences[0]);
                if ($worksheet !== null && $worksheet->cellExists($aReferences[0])) {
                    $returnValue[$currentRow][$currentCol] = $worksheet->getCell($aReferences[0])->getCalculatedValue($resetLog);
                } else {
                    $returnValue[$currentRow][$currentCol] = null;
                }
            } else {
                
                foreach ($aReferences as $reference) {
                    
                    [$currentCol, $currentRow] = Coordinate::coordinateFromString($reference);
                    if ($worksheet !== null && $worksheet->cellExists($reference)) {
                        $returnValue[$currentRow][$currentCol] = $worksheet->getCell($reference)->getCalculatedValue($resetLog);
                    } else {
                        $returnValue[$currentRow][$currentCol] = null;
                    }
                }
            }
        }

        return $returnValue;
    }

    
    public function isImplemented(string $function): bool
    {
        $function = strtoupper($function);
        $phpSpreadsheetFunctions = &self::getFunctionsAddress();
        $notImplemented = !isset($phpSpreadsheetFunctions[$function]) || (is_array($phpSpreadsheetFunctions[$function]['functionCall']) && $phpSpreadsheetFunctions[$function]['functionCall'][1] === 'DUMMY');

        return !$notImplemented;
    }

    
    public function getImplementedFunctionNames(): array
    {
        $returnValue = [];
        $phpSpreadsheetFunctions = &self::getFunctionsAddress();
        foreach ($phpSpreadsheetFunctions as $functionName => $function) {
            if ($this->isImplemented($functionName)) {
                $returnValue[] = $functionName;
            }
        }

        return $returnValue;
    }

    
    private function addDefaultArgumentValues(array $functionCall, array $args, array $emptyArguments): array
    {
        $reflector = new ReflectionMethod($functionCall[0], $functionCall[1]);
        $methodArguments = $reflector->getParameters();

        if (count($methodArguments) > 0) {
            
            foreach ($emptyArguments as $argumentId => $isArgumentEmpty) {
                if ($isArgumentEmpty === true) {
                    $reflectedArgumentId = count($args) - (int) $argumentId - 1;
                    if (
                        !array_key_exists($reflectedArgumentId, $methodArguments)
                        || $methodArguments[$reflectedArgumentId]->isVariadic()
                    ) {
                        break;
                    }

                    $args[$argumentId] = $this->getArgumentDefaultValue($methodArguments[$reflectedArgumentId]);
                }
            }
        }

        return $args;
    }

    private function getArgumentDefaultValue(ReflectionParameter $methodArgument): mixed
    {
        $defaultValue = null;

        if ($methodArgument->isDefaultValueAvailable()) {
            $defaultValue = $methodArgument->getDefaultValue();
            if ($methodArgument->isDefaultValueConstant()) {
                $constantName = $methodArgument->getDefaultValueConstantName() ?? '';
                
                if (str_contains($constantName, '::')) {
                    [$className, $constantName] = explode('::', $constantName);
                    $constantReflector = new ReflectionClassConstant($className, $constantName);

                    return $constantReflector->getValue();
                }

                return constant($constantName);
            }
        }

        return $defaultValue;
    }

    
    private function addCellReference(array $args, bool $passCellReference, array|string $functionCall, ?Cell $cell = null): array
    {
        if ($passCellReference) {
            if (is_array($functionCall)) {
                $className = $functionCall[0];
                $methodName = $functionCall[1];

                $reflectionMethod = new ReflectionMethod($className, $methodName);
                $argumentCount = count($reflectionMethod->getParameters());
                while (count($args) < $argumentCount - 1) {
                    $args[] = null;
                }
            }

            $args[] = $cell;
        }

        return $args;
    }

    private function evaluateDefinedName(Cell $cell, DefinedName $namedRange, Worksheet $cellWorksheet, Stack $stack, bool $ignoreScope = false): mixed
    {
        $definedNameScope = $namedRange->getScope();
        if ($definedNameScope !== null && $definedNameScope !== $cellWorksheet && !$ignoreScope) {
            
            $result = ExcelError::REF();
            $stack->push('Error', $result, $namedRange->getName());

            return $result;
        }

        $definedNameValue = $namedRange->getValue();
        $definedNameType = $namedRange->isFormula() ? 'Formula' : 'Range';
        $definedNameWorksheet = $namedRange->getWorksheet();

        if ($definedNameValue[0] !== '=') {
            $definedNameValue = '=' . $definedNameValue;
        }

        $this->debugLog->writeDebugLog('Defined Name is a %s with a value of %s', $definedNameType, $definedNameValue);

        $originalCoordinate = $cell->getCoordinate();
        $recursiveCalculationCell = ($definedNameType !== 'Formula' && $definedNameWorksheet !== null && $definedNameWorksheet !== $cellWorksheet)
            ? $definedNameWorksheet->getCell('A1')
            : $cell;
        $recursiveCalculationCellAddress = $recursiveCalculationCell->getCoordinate();

        
        $definedNameValue = ReferenceHelper::getInstance()
            ->updateFormulaReferencesAnyWorksheet(
                $definedNameValue,
                Coordinate::columnIndexFromString(
                    $cell->getColumn()
                ) - 1,
                $cell->getRow() - 1
            );

        $this->debugLog->writeDebugLog('Value adjusted for relative references is %s', $definedNameValue);

        $recursiveCalculator = new self($this->spreadsheet);
        $recursiveCalculator->getDebugLog()->setWriteDebugLog($this->getDebugLog()->getWriteDebugLog());
        $recursiveCalculator->getDebugLog()->setEchoDebugLog($this->getDebugLog()->getEchoDebugLog());
        $result = $recursiveCalculator->_calculateFormulaValue($definedNameValue, $recursiveCalculationCellAddress, $recursiveCalculationCell, true);
        $cellWorksheet->getCell($originalCoordinate);

        if ($this->getDebugLog()->getWriteDebugLog()) {
            $this->debugLog->mergeDebugLog(array_slice($recursiveCalculator->getDebugLog()->getLog(), 3));
            $this->debugLog->writeDebugLog('Evaluation Result for Named %s %s is %s', $definedNameType, $namedRange->getName(), $this->showTypeDetails($result));
        }

        $stack->push('Defined Name', $result, $namedRange->getName());

        return $result;
    }

    public function setSuppressFormulaErrors(bool $suppressFormulaErrors): self
    {
        $this->suppressFormulaErrors = $suppressFormulaErrors;

        return $this;
    }

    public function getSuppressFormulaErrors(): bool
    {
        return $this->suppressFormulaErrors;
    }

    public static function boolToString(mixed $operand1): mixed
    {
        if (is_bool($operand1)) {
            $operand1 = ($operand1) ? self::$localeBoolean['TRUE'] : self::$localeBoolean['FALSE'];
        } elseif ($operand1 === null) {
            $operand1 = '';
        }

        return $operand1;
    }

    private static function isNumericOrBool(mixed $operand): bool
    {
        return is_numeric($operand) || is_bool($operand);
    }

    private static function makeError(mixed $operand = ''): string
    {
        return (is_string($operand) && Information\ErrorValue::isError($operand)) ? $operand : ExcelError::VALUE();
    }

    private static function swapOperands(Stack $stack, string $opCharacter): bool
    {
        $retVal = false;
        if ($stack->count() > 0) {
            $o2 = $stack->last();
            if ($o2) {
                if (isset(self::CALCULATION_OPERATORS[$o2['value']])) {
                    $retVal = (self::OPERATOR_PRECEDENCE[$opCharacter] ?? 0) <= self::OPERATOR_PRECEDENCE[$o2['value']];
                }
            }
        }

        return $retVal;
    }

    public function getSpreadsheet(): ?Spreadsheet
    {
        return $this->spreadsheet;
    }
}
