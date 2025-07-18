<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Styles as StyleReader;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalColorScale;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalDataBar;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormattingRuleExtension;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject;
use PhpOffice\PhpSpreadsheet\Style\Style as Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SimpleXMLElement;
use stdClass;

class ConditionalStyles
{
    private Worksheet $worksheet;

    private SimpleXMLElement $worksheetXml;

    private array $ns;

    private array $dxfs;

    private StyleReader $styleReader;

    public function __construct(Worksheet $workSheet, SimpleXMLElement $worksheetXml, array $dxfs, StyleReader $styleReader)
    {
        $this->worksheet = $workSheet;
        $this->worksheetXml = $worksheetXml;
        $this->dxfs = $dxfs;
        $this->styleReader = $styleReader;
    }

    public function load(): void
    {
        $selectedCells = $this->worksheet->getSelectedCells();

        $this->setConditionalStyles(
            $this->worksheet,
            $this->readConditionalStyles($this->worksheetXml),
            $this->worksheetXml->extLst
        );

        $this->worksheet->setSelectedCells($selectedCells);
    }

    public function loadFromExt(): void
    {
        $selectedCells = $this->worksheet->getSelectedCells();

        $this->ns = $this->worksheetXml->getNamespaces(true);
        $this->setConditionalsFromExt(
            $this->readConditionalsFromExt($this->worksheetXml->extLst)
        );

        $this->worksheet->setSelectedCells($selectedCells);
    }

    private function setConditionalsFromExt(array $conditionals): void
    {
        foreach ($conditionals as $conditionalRange => $cfRules) {
            ksort($cfRules);
            
            
            $this->worksheet->getStyle($conditionalRange)
                ->setConditionalStyles(array_values($cfRules));
        }
    }

    private function readConditionalsFromExt(SimpleXMLElement $extLst): array
    {
        $conditionals = [];
        if (!isset($extLst->ext)) {
            return $conditionals;
        }

        foreach ($extLst->ext as $extlstcond) {
            $extAttrs = $extlstcond->attributes() ?? [];
            $extUri = (string) ($extAttrs['uri'] ?? '');
            if ($extUri !== '{78C0D931-6437-407d-A8EE-F0AAD7539E65}') {
                continue;
            }
            $conditionalFormattingRuleXml = $extlstcond->children($this->ns['x14']);
            if (!$conditionalFormattingRuleXml->conditionalFormattings) {
                return [];
            }

            foreach ($conditionalFormattingRuleXml->children($this->ns['x14']) as $extFormattingXml) {
                $extFormattingRangeXml = $extFormattingXml->children($this->ns['xm']);
                if (!$extFormattingRangeXml->sqref) {
                    continue;
                }

                $sqref = (string) $extFormattingRangeXml->sqref;
                $extCfRuleXml = $extFormattingXml->cfRule;

                $attributes = $extCfRuleXml->attributes();
                if (!$attributes) {
                    continue;
                }
                $conditionType = (string) $attributes->type;
                if (
                    !Conditional::isValidConditionType($conditionType)
                    || $conditionType === Conditional::CONDITION_DATABAR
                ) {
                    continue;
                }

                $priority = (int) $attributes->priority;

                $conditional = $this->readConditionalRuleFromExt($extCfRuleXml, $attributes);
                $cfStyle = $this->readStyleFromExt($extCfRuleXml);
                $conditional->setStyle($cfStyle);
                $conditionals[$sqref][$priority] = $conditional;
            }
        }

        return $conditionals;
    }

    private function readConditionalRuleFromExt(SimpleXMLElement $cfRuleXml, SimpleXMLElement $attributes): Conditional
    {
        $conditionType = (string) $attributes->type;
        $operatorType = (string) $attributes->operator;
        $priority = (int) (string) $attributes->priority;

        $operands = [];
        foreach ($cfRuleXml->children($this->ns['xm']) as $cfRuleOperandsXml) {
            $operands[] = (string) $cfRuleOperandsXml;
        }

        $conditional = new Conditional();
        $conditional->setConditionType($conditionType);
        $conditional->setOperatorType($operatorType);
        $conditional->setPriority($priority);
        if (
            $conditionType === Conditional::CONDITION_CONTAINSTEXT
            || $conditionType === Conditional::CONDITION_NOTCONTAINSTEXT
            || $conditionType === Conditional::CONDITION_BEGINSWITH
            || $conditionType === Conditional::CONDITION_ENDSWITH
            || $conditionType === Conditional::CONDITION_TIMEPERIOD
        ) {
            $conditional->setText(array_pop($operands) ?? '');
        }
        $conditional->setConditions($operands);

        return $conditional;
    }

    private function readStyleFromExt(SimpleXMLElement $extCfRuleXml): Style
    {
        $cfStyle = new Style(false, true);
        if ($extCfRuleXml->dxf) {
            $styleXML = $extCfRuleXml->dxf->children();

            if ($styleXML->borders) {
                $this->styleReader->readBorderStyle($cfStyle->getBorders(), $styleXML->borders);
            }
            if ($styleXML->fill) {
                $this->styleReader->readFillStyle($cfStyle->getFill(), $styleXML->fill);
            }
        }

        return $cfStyle;
    }

    private function readConditionalStyles(SimpleXMLElement $xmlSheet): array
    {
        $conditionals = [];
        foreach ($xmlSheet->conditionalFormatting as $conditional) {
            foreach ($conditional->cfRule as $cfRule) {
                if (Conditional::isValidConditionType((string) $cfRule['type']) && (!isset($cfRule['dxfId']) || isset($this->dxfs[(int) ($cfRule['dxfId'])]))) {
                    $conditionals[(string) $conditional['sqref']][(int) ($cfRule['priority'])] = $cfRule;
                } elseif ((string) $cfRule['type'] == Conditional::CONDITION_DATABAR) {
                    $conditionals[(string) $conditional['sqref']][(int) ($cfRule['priority'])] = $cfRule;
                }
            }
        }

        return $conditionals;
    }

    private function setConditionalStyles(Worksheet $worksheet, array $conditionals, SimpleXMLElement $xmlExtLst): void
    {
        foreach ($conditionals as $cellRangeReference => $cfRules) {
            ksort($cfRules); 
            $conditionalStyles = $this->readStyleRules($cfRules, $xmlExtLst);

            
            
            
            $cellRangeReference = str_replace(['$', ' ', ',', '^'], ['', '^', ' ', ','], strtoupper($cellRangeReference));

            foreach ($conditionalStyles as $cs) {
                $scale = $cs->getColorScale();
                if ($scale !== null) {
                    $scale->setSqRef($cellRangeReference, $worksheet);
                }
            }
            $worksheet->getStyle($cellRangeReference)->setConditionalStyles($conditionalStyles);
        }
    }

    private function readStyleRules(array $cfRules, SimpleXMLElement $extLst): array
    {
        $conditionalFormattingRuleExtensions = ConditionalFormattingRuleExtension::parseExtLstXml($extLst);
        $conditionalStyles = [];

        
        foreach ($cfRules as $cfRule) {
            $objConditional = new Conditional();
            $objConditional->setConditionType((string) $cfRule['type']);
            $objConditional->setOperatorType((string) $cfRule['operator']);
            $objConditional->setPriority((int) (string) $cfRule['priority']);
            $objConditional->setNoFormatSet(!isset($cfRule['dxfId']));

            if ((string) $cfRule['text'] != '') {
                $objConditional->setText((string) $cfRule['text']);
            } elseif ((string) $cfRule['timePeriod'] != '') {
                $objConditional->setText((string) $cfRule['timePeriod']);
            }

            if (isset($cfRule['stopIfTrue']) && (int) $cfRule['stopIfTrue'] === 1) {
                $objConditional->setStopIfTrue(true);
            }

            if (count($cfRule->formula) >= 1) {
                foreach ($cfRule->formula as $formulax) {
                    $formula = (string) $formulax;
                    $formula = str_replace(['_xlfn.', '_xlws.'], '', $formula);
                    if ($formula === 'TRUE') {
                        $objConditional->addCondition(true);
                    } elseif ($formula === 'FALSE') {
                        $objConditional->addCondition(false);
                    } else {
                        $objConditional->addCondition($formula);
                    }
                }
            } else {
                $objConditional->addCondition('');
            }

            if (isset($cfRule->dataBar)) {
                $objConditional->setDataBar(
                    $this->readDataBarOfConditionalRule($cfRule, $conditionalFormattingRuleExtensions)
                );
            } elseif (isset($cfRule->colorScale)) {
                $objConditional->setColorScale(
                    $this->readColorScale($cfRule)
                );
            } elseif (isset($cfRule['dxfId'])) {
                $objConditional->setStyle(clone $this->dxfs[(int) ($cfRule['dxfId'])]);
            }

            $conditionalStyles[] = $objConditional;
        }

        return $conditionalStyles;
    }

    private function readDataBarOfConditionalRule(SimpleXMLElement $cfRule, array $conditionalFormattingRuleExtensions): ConditionalDataBar
    {
        $dataBar = new ConditionalDataBar();
        
        if (isset($cfRule->dataBar['showValue'])) {
            $dataBar->setShowValue((bool) $cfRule->dataBar['showValue']);
        }

        
        
        $cfvoXml = $cfRule->dataBar->cfvo;
        $cfvoIndex = 0;
        foreach ((count($cfvoXml) > 1 ? $cfvoXml : [$cfvoXml]) as $cfvo) { 
            if ($cfvoIndex === 0) {
                $dataBar->setMinimumConditionalFormatValueObject(new ConditionalFormatValueObject((string) $cfvo['type'], (string) $cfvo['val']));
            }
            if ($cfvoIndex === 1) {
                $dataBar->setMaximumConditionalFormatValueObject(new ConditionalFormatValueObject((string) $cfvo['type'], (string) $cfvo['val']));
            }
            ++$cfvoIndex;
        }

        
        if (isset($cfRule->dataBar->color)) {
            $dataBar->setColor($this->styleReader->readColor($cfRule->dataBar->color));
        }
        
        $this->readDataBarExtLstOfConditionalRule($dataBar, $cfRule, $conditionalFormattingRuleExtensions);

        return $dataBar;
    }

    private function readColorScale(SimpleXMLElement|stdClass $cfRule): ConditionalColorScale
    {
        $colorScale = new ConditionalColorScale();
        $count = count($cfRule->colorScale->cfvo);
        $idx = 0;
        foreach ($cfRule->colorScale->cfvo as $cfvoXml) {
            $attr = $cfvoXml->attributes() ?? [];
            $type = (string) ($attr['type'] ?? '');
            $val = $attr['val'] ?? null;
            if ($idx === 0) {
                $method = 'setMinimumConditionalFormatValueObject';
            } elseif ($idx === 1 && $count === 3) {
                $method = 'setMidpointConditionalFormatValueObject';
            } else {
                $method = 'setMaximumConditionalFormatValueObject';
            }
            if ($type !== 'formula') {
                $colorScale->$method(new ConditionalFormatValueObject($type, $val));
            } else {
                $colorScale->$method(new ConditionalFormatValueObject($type, null, $val));
            }
            ++$idx;
        }
        $idx = 0;
        foreach ($cfRule->colorScale->color as $color) {
            $rgb = $this->styleReader->readColor($color);
            if ($idx === 0) {
                $colorScale->setMinimumColor(new Color($rgb));
            } elseif ($idx === 1 && $count === 3) {
                $colorScale->setMidpointColor(new Color($rgb));
            } else {
                $colorScale->setMaximumColor(new Color($rgb));
            }
            ++$idx;
        }

        return $colorScale;
    }

    private function readDataBarExtLstOfConditionalRule(ConditionalDataBar $dataBar, SimpleXMLElement $cfRule, array $conditionalFormattingRuleExtensions): void
    {
        if (isset($cfRule->extLst)) {
            $ns = $cfRule->extLst->getNamespaces(true);
            foreach ((count($cfRule->extLst) > 0 ? $cfRule->extLst->ext : [$cfRule->extLst->ext]) as $ext) { 
                $extId = (string) $ext->children($ns['x14'])->id;
                if (isset($conditionalFormattingRuleExtensions[$extId]) && (string) $ext['uri'] === '{B025F937-C7B1-47D3-B67F-A62EFF666E3E}') {
                    $dataBar->setConditionalFormattingRuleExt($conditionalFormattingRuleExtensions[$extId]);
                }
            }
        }
    }
}
