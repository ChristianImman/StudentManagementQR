<?php

namespace PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CellMatcher
{
    public const COMPARISON_OPERATORS = [
        Conditional::OPERATOR_EQUAL => '=',
        Conditional::OPERATOR_GREATERTHAN => '>',
        Conditional::OPERATOR_GREATERTHANOREQUAL => '>=',
        Conditional::OPERATOR_LESSTHAN => '<',
        Conditional::OPERATOR_LESSTHANOREQUAL => '<=',
        Conditional::OPERATOR_NOTEQUAL => '<>',
    ];

    public const COMPARISON_RANGE_OPERATORS = [
        Conditional::OPERATOR_BETWEEN => 'IF(AND(A1>=%s,A1<=%s),TRUE,FALSE)',
        Conditional::OPERATOR_NOTBETWEEN => 'IF(AND(A1>=%s,A1<=%s),FALSE,TRUE)',
    ];

    public const COMPARISON_DUPLICATES_OPERATORS = [
        Conditional::CONDITION_DUPLICATES => "COUNTIF('%s'!%s,%s)>1",
        Conditional::CONDITION_UNIQUE => "COUNTIF('%s'!%s,%s)=1",
    ];

    protected Cell $cell;

    protected int $cellRow;

    protected Worksheet $worksheet;

    protected int $cellColumn;

    protected string $conditionalRange;

    protected string $referenceCell;

    protected int $referenceRow;

    protected int $referenceColumn;

    protected Calculation $engine;

    public function __construct(Cell $cell, string $conditionalRange)
    {
        $this->cell = $cell;
        $this->worksheet = $cell->getWorksheet();
        [$this->cellColumn, $this->cellRow] = Coordinate::indexesFromString($this->cell->getCoordinate());
        $this->setReferenceCellForExpressions($conditionalRange);

        $this->engine = Calculation::getInstance($this->worksheet->getParent());
    }

    protected function setReferenceCellForExpressions(string $conditionalRange): void
    {
        $conditionalRange = Coordinate::splitRange(str_replace('$', '', strtoupper($conditionalRange)));
        [$this->referenceCell] = $conditionalRange[0];

        [$this->referenceColumn, $this->referenceRow] = Coordinate::indexesFromString($this->referenceCell);

        
        $rangeSets = [];
        foreach ($conditionalRange as $rangeSet) {
            $absoluteRangeSet = array_map(
                [Coordinate::class, 'absoluteCoordinate'],
                $rangeSet
            );
            $rangeSets[] = implode(':', $absoluteRangeSet);
        }
        $this->conditionalRange = implode(',', $rangeSets);
    }

    public function evaluateConditional(Conditional $conditional): bool
    {
        
        $cellColumn = Coordinate::stringFromColumnIndex($this->cellColumn);
        $cellAddress = "{$cellColumn}{$this->cellRow}";
        $this->cell = $this->worksheet->getCell($cellAddress);

        return match ($conditional->getConditionType()) {
            Conditional::CONDITION_CELLIS => $this->processOperatorComparison($conditional),
            Conditional::CONDITION_DUPLICATES, Conditional::CONDITION_UNIQUE => $this->processDuplicatesComparison($conditional),
            
            Conditional::CONDITION_CONTAINSTEXT,
            
            Conditional::CONDITION_NOTCONTAINSTEXT,
            
            Conditional::CONDITION_BEGINSWITH,
            
            Conditional::CONDITION_ENDSWITH,
            
            Conditional::CONDITION_CONTAINSBLANKS,
            
            Conditional::CONDITION_NOTCONTAINSBLANKS,
            
            Conditional::CONDITION_CONTAINSERRORS,
            
            Conditional::CONDITION_NOTCONTAINSERRORS,
            
            
            
            
            
            Conditional::CONDITION_TIMEPERIOD,
            Conditional::CONDITION_EXPRESSION => $this->processExpression($conditional),
            Conditional::CONDITION_COLORSCALE => $this->processColorScale($conditional),
            default => false,
        };
    }

    protected function wrapValue(mixed $value): float|int|string
    {
        if (!is_numeric($value)) {
            if (is_bool($value)) {
                return $value ? 'TRUE' : 'FALSE';
            } elseif ($value === null) {
                return 'NULL';
            }

            return '"' . StringHelper::convertToString($value) . '"';
        }

        return $value;
    }

    protected function wrapCellValue(): float|int|string
    {
        $this->cell = $this->worksheet->getCell([$this->cellColumn, $this->cellRow]);

        return $this->wrapValue($this->cell->getCalculatedValue());
    }

    protected function conditionCellAdjustment(array $matches): float|int|string
    {
        $column = $matches[6];
        $row = $matches[7];
        if (!str_contains($column, '$')) {
            
            $column = Coordinate::columnIndexFromString($column);
            $column += $this->cellColumn - $this->referenceColumn;
            $column = Coordinate::stringFromColumnIndex($column);
        }

        if (!str_contains($row, '$')) {
            $row += $this->cellRow - $this->referenceRow;
        }

        if (!empty($matches[4])) {
            $worksheet = $this->worksheet->getParentOrThrow()->getSheetByName(trim($matches[4], "'"));
            if ($worksheet === null) {
                return $this->wrapValue(null);
            }

            return $this->wrapValue(
                $worksheet
                    ->getCell(str_replace('$', '', "{$column}{$row}"))
                    ->getCalculatedValue()
            );
        }

        return $this->wrapValue(
            $this->worksheet
                ->getCell(str_replace('$', '', "{$column}{$row}"))
                ->getCalculatedValue()
        );
    }

    protected function cellConditionCheck(string $condition): string
    {
        $splitCondition = explode(Calculation::FORMULA_STRING_QUOTE, $condition);
        $i = false;
        foreach ($splitCondition as &$value) {
            
            $i = $i === false;
            if ($i) {
                $value = (string) preg_replace_callback(
                    '/' . Calculation::CALCULATION_REGEXP_CELLREF_RELATIVE . '/i',
                    [$this, 'conditionCellAdjustment'],
                    $value
                );
            }
        }
        unset($value);

        
        return implode(Calculation::FORMULA_STRING_QUOTE, $splitCondition);
    }

    protected function adjustConditionsForCellReferences(array $conditions): array
    {
        return array_map(
            [$this, 'cellConditionCheck'],
            $conditions
        );
    }

    protected function processOperatorComparison(Conditional $conditional): bool
    {
        if (array_key_exists($conditional->getOperatorType(), self::COMPARISON_RANGE_OPERATORS)) {
            return $this->processRangeOperator($conditional);
        }

        $operator = self::COMPARISON_OPERATORS[$conditional->getOperatorType()];
        $conditions = $this->adjustConditionsForCellReferences($conditional->getConditions());
        $expression = sprintf('%s%s%s', (string) $this->wrapCellValue(), $operator, (string) array_pop($conditions));

        return $this->evaluateExpression($expression);
    }

    protected function processColorScale(Conditional $conditional): bool
    {
        if (is_numeric($this->wrapCellValue()) && $conditional->getColorScale()?->colorScaleReadyForUse()) {
            return true;
        }

        return false;
    }

    protected function processRangeOperator(Conditional $conditional): bool
    {
        $conditions = $this->adjustConditionsForCellReferences($conditional->getConditions());
        sort($conditions);
        $expression = sprintf(
            (string) preg_replace(
                '/\bA1\b/i',
                (string) $this->wrapCellValue(),
                self::COMPARISON_RANGE_OPERATORS[$conditional->getOperatorType()]
            ),
            ...$conditions
        );

        return $this->evaluateExpression($expression);
    }

    protected function processDuplicatesComparison(Conditional $conditional): bool
    {
        $worksheetName = $this->cell->getWorksheet()->getTitle();

        $expression = sprintf(
            self::COMPARISON_DUPLICATES_OPERATORS[$conditional->getConditionType()],
            $worksheetName,
            $this->conditionalRange,
            $this->cellConditionCheck($this->cell->getCalculatedValueString())
        );

        return $this->evaluateExpression($expression);
    }

    protected function processExpression(Conditional $conditional): bool
    {
        $conditions = $this->adjustConditionsForCellReferences($conditional->getConditions());
        $expression = array_pop($conditions);

        $expression = (string) preg_replace(
            '/\b' . $this->referenceCell . '\b/i',
            (string) $this->wrapCellValue(),
            $expression
        );

        return $this->evaluateExpression($expression);
    }

    protected function evaluateExpression(string $expression): bool
    {
        $expression = "={$expression}";

        try {
            $this->engine->flushInstance();
            $result = (bool) $this->engine->calculateFormula($expression);
        } catch (Exception) {
            return false;
        }

        return $result;
    }
}
