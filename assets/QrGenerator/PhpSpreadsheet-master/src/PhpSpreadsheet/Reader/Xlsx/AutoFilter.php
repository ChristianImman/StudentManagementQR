<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column\Rule;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SimpleXMLElement;

class AutoFilter
{
    private Table|Worksheet $parent;

    private SimpleXMLElement $worksheetXml;

    public function __construct(Table|Worksheet $parent, SimpleXMLElement $worksheetXml)
    {
        $this->parent = $parent;
        $this->worksheetXml = $worksheetXml;
    }

    public function load(): void
    {
        
        $attrs = $this->worksheetXml->autoFilter->attributes() ?? [];
        $autoFilterRange = (string) preg_replace('/\$/', '', $attrs['ref'] ?? '');
        if (str_contains($autoFilterRange, ':')) {
            $this->readAutoFilter($autoFilterRange);
        }
    }

    private function readAutoFilter(string $autoFilterRange): void
    {
        $autoFilter = $this->parent->getAutoFilter();
        $autoFilter->setRange($autoFilterRange);

        foreach ($this->worksheetXml->autoFilter->filterColumn as $filterColumn) {
            $attributes = $filterColumn->attributes() ?? [];
            $column = $autoFilter->getColumnByOffset((int) ($attributes['colId'] ?? 0));
            
            if ($filterColumn->filters) {
                $column->setFilterType(Column::AUTOFILTER_FILTERTYPE_FILTER);
                $filters = Xlsx::testSimpleXml($filterColumn->filters->attributes());
                if ((isset($filters['blank'])) && ((int) $filters['blank'] == 1)) {
                    
                    $column->createRule()->setRule('', '')->setRuleType(Rule::AUTOFILTER_RULETYPE_FILTER);
                }
                
                
                foreach ($filterColumn->filters->filter as $filterRule) {
                    
                    $attr2 = $filterRule->attributes() ?? ['val' => ''];
                    $column->createRule()->setRule('', (string) $attr2['val'])->setRuleType(Rule::AUTOFILTER_RULETYPE_FILTER);
                }

                
                $this->readDateRangeAutoFilter($filterColumn->filters, $column);
            }

            
            $this->readCustomAutoFilter($filterColumn, $column);
            
            $this->readDynamicAutoFilter($filterColumn, $column);
            
            $this->readTopTenAutoFilter($filterColumn, $column);
        }
        $autoFilter->setEvaluated(true);
    }

    private function readDateRangeAutoFilter(SimpleXMLElement $filters, Column $column): void
    {
        foreach ($filters->dateGroupItem as $dateGroupItemx) {
            
            $dateGroupItem = $dateGroupItemx->attributes();
            if ($dateGroupItem !== null) {
                $column->createRule()->setRule(
                    '',
                    [
                        'year' => (string) $dateGroupItem['year'],
                        'month' => (string) $dateGroupItem['month'],
                        'day' => (string) $dateGroupItem['day'],
                        'hour' => (string) $dateGroupItem['hour'],
                        'minute' => (string) $dateGroupItem['minute'],
                        'second' => (string) $dateGroupItem['second'],
                    ],
                    (string) $dateGroupItem['dateTimeGrouping']
                )->setRuleType(Rule::AUTOFILTER_RULETYPE_DATEGROUP);
            }
        }
    }

    private function readCustomAutoFilter(?SimpleXMLElement $filterColumn, Column $column): void
    {
        if (isset($filterColumn, $filterColumn->customFilters)) {
            $column->setFilterType(Column::AUTOFILTER_FILTERTYPE_CUSTOMFILTER);
            $customFilters = $filterColumn->customFilters;
            $attributes = $customFilters->attributes();
            
            
            if ((isset($attributes['and'])) && ((string) $attributes['and'] === '1')) {
                $column->setJoin(Column::AUTOFILTER_COLUMN_JOIN_AND);
            }
            foreach ($customFilters->customFilter as $filterRule) {
                $attr2 = $filterRule->attributes() ?? ['operator' => '', 'val' => ''];
                $column->createRule()->setRule(
                    (string) $attr2['operator'],
                    (string) $attr2['val']
                )->setRuleType(Rule::AUTOFILTER_RULETYPE_CUSTOMFILTER);
            }
        }
    }

    private function readDynamicAutoFilter(?SimpleXMLElement $filterColumn, Column $column): void
    {
        if (isset($filterColumn, $filterColumn->dynamicFilter)) {
            $column->setFilterType(Column::AUTOFILTER_FILTERTYPE_DYNAMICFILTER);
            
            foreach ($filterColumn->dynamicFilter as $filterRule) {
                
                $attr2 = $filterRule->attributes() ?? [];
                $column->createRule()->setRule(
                    '',
                    (string) ($attr2['val'] ?? ''),
                    (string) ($attr2['type'] ?? '')
                )->setRuleType(Rule::AUTOFILTER_RULETYPE_DYNAMICFILTER);
                if (isset($attr2['val'])) {
                    $column->setAttribute('val', (string) $attr2['val']);
                }
                if (isset($attr2['maxVal'])) {
                    $column->setAttribute('maxVal', (string) $attr2['maxVal']);
                }
            }
        }
    }

    private function readTopTenAutoFilter(?SimpleXMLElement $filterColumn, Column $column): void
    {
        if (isset($filterColumn, $filterColumn->top10)) {
            $column->setFilterType(Column::AUTOFILTER_FILTERTYPE_TOPTENFILTER);
            
            foreach ($filterColumn->top10 as $filterRule) {
                $attr2 = $filterRule->attributes() ?? [];
                $column->createRule()->setRule(
                    (
                        ((isset($attr2['percent'])) && ((string) $attr2['percent'] === '1'))
                        ? Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_PERCENT
                        : Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_BY_VALUE
                    ),
                    (string) ($attr2['val'] ?? ''),
                    (
                        ((isset($attr2['top'])) && ((string) $attr2['top'] === '1'))
                        ? Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_TOP
                        : Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_BOTTOM
                    )
                )->setRuleType(Rule::AUTOFILTER_RULETYPE_TOPTENFILTER);
            }
        }
    }
}
