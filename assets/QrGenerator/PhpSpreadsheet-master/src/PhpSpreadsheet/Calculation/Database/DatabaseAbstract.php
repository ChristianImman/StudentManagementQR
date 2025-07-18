<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Internal\WildcardMatch;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

abstract class DatabaseAbstract
{
    
    abstract public static function evaluate(array $database, array|null|int|string $field, array $criteria): null|float|int|string;

    
    protected static function fieldExtract(array $database, mixed $field): ?int
    {
        
        $single = Functions::flattenSingleValue($field);
        $field = strtoupper($single ?? '');
        if ($field === '') {
            return null;
        }

        
        $callable = 'strtoupper';
        
        $fieldNames = array_map($callable, array_shift($database));
        if (is_numeric($field)) {
            $field = (int) $field - 1;
            if ($field < 0 || $field >= count($fieldNames)) {
                return null;
            }

            return $field;
        }
        $key = array_search($field, array_values($fieldNames), true);

        return ($key !== false) ? (int) $key : null;
    }

    
    protected static function filter(array $database, array $criteria): array
    {
        
        $fieldNames = array_shift($database);
        
        $criteriaNames = array_shift($criteria);

        
        $query = self::buildQuery($criteriaNames, $criteria);

        
        return self::executeQuery($database, $query, $criteriaNames, $fieldNames);
    }

    
    protected static function getFilteredColumn(array $database, ?int $field, array $criteria): array
    {
        
        $database = self::filter($database, $criteria);
        $defaultReturnColumnValue = ($field === null) ? 1 : null;

        
        $columnData = [];
        
        foreach ($database as $rowKey => $row) {
            $keys = array_keys($row);
            $key = $keys[$field] ?? null;
            $columnKey = $key ?? 'A';
            $columnData[$rowKey][$columnKey] = $row[$key] ?? $defaultReturnColumnValue;
        }

        return $columnData;
    }

    
    private static function buildQuery(array $criteriaNames, array $criteria): string
    {
        $baseQuery = [];
        foreach ($criteria as $key => $criterion) {
            foreach ($criterion as $field => $value) {
                $criterionName = $criteriaNames[$field];
                if ($value !== null) {
                    $condition = self::buildCondition($value, $criterionName);
                    $baseQuery[$key][] = $condition;
                }
            }
        }

        $rowQuery = array_map(
            fn ($rowValue): string => (count($rowValue) > 1) ? 'AND(' . implode(',', $rowValue) . ')' : ($rowValue[0] ?? ''), 
            $baseQuery
        );

        return (count($rowQuery) > 1) ? 'OR(' . implode(',', $rowQuery) . ')' : ($rowQuery[0] ?? '');
    }

    private static function buildCondition(mixed $criterion, string $criterionName): string
    {
        $ifCondition = Functions::ifCondition($criterion);

        
        $result = preg_match('/(?<operator>[^"]*)(?<operand>".*[*?].*")/ui', $ifCondition, $matches);
        if ($result !== 1) {
            return "[:{$criterionName}]{$ifCondition}";
        }

        $trueFalse = ($matches['operator'] !== '<>');
        $wildcard = WildcardMatch::wildcard($matches['operand']);
        $condition = "WILDCARDMATCH([:{$criterionName}],{$wildcard})";
        if ($trueFalse === false) {
            $condition = "NOT({$condition})";
        }

        return $condition;
    }

    
    private static function executeQuery(array $database, string $query, array $criteria, array $fields): array
    {
        foreach ($database as $dataRow => $dataValues) {
            
            $conditions = $query;
            foreach ($criteria as $criterion) {
                
                
                $conditions = self::processCondition($criterion, $fields, $dataValues, $conditions);
            }

            
            $result = Calculation::getInstance()->_calculateFormulaValue('=' . $conditions);

            
            if ($result !== true) {
                unset($database[$dataRow]);
            }
        }

        return $database;
    }

    
    private static function processCondition(string $criterion, array $fields, array $dataValues, string $conditions): string
    {
        $key = array_search($criterion, $fields, true);

        $dataValue = 'NULL';
        if (is_bool($dataValues[$key])) {
            $dataValue = ($dataValues[$key]) ? 'TRUE' : 'FALSE';
        } elseif ($dataValues[$key] !== null) {
            $dataValue = $dataValues[$key];
            
            if (is_string($dataValue) && str_contains($dataValue, '"')) {
                $dataValue = str_replace('"', '""', $dataValue);
            }
            if (is_string($dataValue)) {
                $dataValue = Calculation::wrapResult(strtoupper($dataValue));
            }
            $dataValue = StringHelper::convertToString($dataValue);
        }

        return str_replace('[:' . $criterion . ']', $dataValue, $conditions);
    }
}
