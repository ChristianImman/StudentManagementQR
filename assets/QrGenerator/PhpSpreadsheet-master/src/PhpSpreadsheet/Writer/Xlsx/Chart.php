<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\ChartColor;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Properties;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\TrendLine;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

class Chart extends WriterPart
{
    private int $seriesIndex;

    
    public function writeChart(\PhpOffice\PhpSpreadsheet\Chart\Chart $chart, bool $calculateCellValues = true): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }
        
        if ($calculateCellValues) {
            $chart->refresh();
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('c:chartSpace');
        $objWriter->writeAttribute('xmlns:c', Namespaces::CHART);
        $objWriter->writeAttribute('xmlns:a', Namespaces::DRAWINGML);
        $objWriter->writeAttribute('xmlns:r', Namespaces::SCHEMA_OFFICE_DOCUMENT);

        $objWriter->startElement('c:date1904');
        $objWriter->writeAttribute('val', '0');
        $objWriter->endElement();
        $objWriter->startElement('c:lang');
        $objWriter->writeAttribute('val', 'en-GB');
        $objWriter->endElement();
        $objWriter->startElement('c:roundedCorners');
        $objWriter->writeAttribute('val', $chart->getRoundedCorners() ? '1' : '0');
        $objWriter->endElement();

        $this->writeAlternateContent($objWriter);

        $objWriter->startElement('c:chart');

        $this->writeTitle($objWriter, $chart->getTitle());

        $objWriter->startElement('c:autoTitleDeleted');
        $objWriter->writeAttribute('val', (string) (int) $chart->getAutoTitleDeleted());
        $objWriter->endElement();

        $objWriter->startElement('c:view3D');
        $surface2D = false;
        $plotArea = $chart->getPlotArea();
        if ($plotArea !== null) {
            $seriesArray = $plotArea->getPlotGroup();
            foreach ($seriesArray as $series) {
                if ($series->getPlotType() === DataSeries::TYPE_SURFACECHART) {
                    $surface2D = true;

                    break;
                }
            }
        }
        $this->writeView3D($objWriter, $chart->getRotX(), 'c:rotX', $surface2D, 90);
        $this->writeView3D($objWriter, $chart->getRotY(), 'c:rotY', $surface2D);
        $this->writeView3D($objWriter, $chart->getRAngAx(), 'c:rAngAx', $surface2D);
        $this->writeView3D($objWriter, $chart->getPerspective(), 'c:perspective', $surface2D);
        $objWriter->endElement(); 

        $this->writePlotArea($objWriter, $chart->getPlotArea(), $chart->getXAxisLabel(), $chart->getYAxisLabel(), $chart->getChartAxisX(), $chart->getChartAxisY());

        $this->writeLegend($objWriter, $chart->getLegend());

        $objWriter->startElement('c:plotVisOnly');
        $objWriter->writeAttribute('val', (string) (int) $chart->getPlotVisibleOnly());
        $objWriter->endElement();

        $objWriter->startElement('c:dispBlanksAs');
        $objWriter->writeAttribute('val', $chart->getDisplayBlanksAs());
        $objWriter->endElement();

        $objWriter->startElement('c:showDLblsOverMax');
        $objWriter->writeAttribute('val', '0');
        $objWriter->endElement();

        $objWriter->endElement(); 

        $objWriter->startElement('c:spPr');
        if ($chart->getNoFill()) {
            $objWriter->startElement('a:noFill');
            $objWriter->endElement(); 
        }
        $fillColor = $chart->getFillColor();
        if ($fillColor->isUsable()) {
            $this->writeColor($objWriter, $fillColor);
        }
        $borderLines = $chart->getBorderLines();
        $this->writeLineStyles($objWriter, $borderLines, $chart->getNoBorder());
        $this->writeEffects($objWriter, $borderLines);
        $objWriter->endElement(); 

        $this->writePrintSettings($objWriter);

        $objWriter->endElement(); 

        
        return $objWriter->getData();
    }

    private function writeView3D(XMLWriter $objWriter, ?int $value, string $tag, bool $surface2D, int $default = 0): void
    {
        if ($value === null && $surface2D) {
            $value = $default;
        }
        if ($value !== null) {
            $objWriter->startElement($tag);
            $objWriter->writeAttribute('val', "$value");
            $objWriter->endElement();
        }
    }

    
    private function writeTitle(XMLWriter $objWriter, ?Title $title = null): void
    {
        if ($title === null) {
            return;
        }
        if ($this->writeCalculatedTitle($objWriter, $title)) {
            return;
        }

        $objWriter->startElement('c:title');
        $caption = $title->getCaption();
        $objWriter->startElement('c:tx');
        $objWriter->startElement('c:rich');

        $objWriter->startElement('a:bodyPr');
        $objWriter->endElement(); 

        $objWriter->startElement('a:lstStyle');
        $objWriter->endElement(); 

        $objWriter->startElement('a:p');
        $objWriter->startElement('a:pPr');
        $objWriter->startElement('a:defRPr');
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        if (is_array($caption)) {
            $caption = $caption[0] ?? '';
        }
        $this->getParentWriter()->getWriterPartstringtable()->writeRichTextForCharts($objWriter, $caption, 'a');

        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $this->writeLayout($objWriter, $title->getLayout());

        $objWriter->startElement('c:overlay');
        $objWriter->writeAttribute('val', ($title->getOverlay()) ? '1' : '0');
        $objWriter->endElement(); 

        $objWriter->endElement(); 
    }

    
    private function writeCalculatedTitle(XMLWriter $objWriter, Title $title): bool
    {
        $calc = $title->getCalculatedTitle($this->getParentWriter()->getSpreadsheet());
        if (empty($calc)) {
            return false;
        }

        $objWriter->startElement('c:title');
        $objWriter->startElement('c:tx');
        $objWriter->startElement('c:strRef');
        $objWriter->writeElement('c:f', $title->getCellReference());
        $objWriter->startElement('c:strCache');

        $objWriter->startElement('c:ptCount');
        $objWriter->writeAttribute('val', '1');
        $objWriter->endElement(); 
        $objWriter->startElement('c:pt');
        $objWriter->writeAttribute('idx', '0');
        $objWriter->writeElement('c:v', $calc);
        $objWriter->endElement(); 

        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $this->writeLayout($objWriter, $title->getLayout());

        $objWriter->startElement('c:overlay');
        $objWriter->writeAttribute('val', ($title->getOverlay()) ? '1' : '0');
        $objWriter->endElement(); 
        

        
        $labelFont = $title->getFont();
        if ($labelFont !== null) {
            $objWriter->startElement('c:txPr');

            $objWriter->startElement('a:bodyPr');
            $objWriter->endElement(); 
            $objWriter->startElement('a:lstStyle');
            $objWriter->endElement(); 
            $this->writeLabelFont($objWriter, $labelFont, null);

            $objWriter->endElement(); 
        }

        $objWriter->endElement(); 

        return true;
    }

    
    private function writeLegend(XMLWriter $objWriter, ?Legend $legend = null): void
    {
        if ($legend === null) {
            return;
        }

        $objWriter->startElement('c:legend');

        $objWriter->startElement('c:legendPos');
        $objWriter->writeAttribute('val', $legend->getPosition());
        $objWriter->endElement();

        $this->writeLayout($objWriter, $legend->getLayout());

        $objWriter->startElement('c:overlay');
        $objWriter->writeAttribute('val', ($legend->getOverlay()) ? '1' : '0');
        $objWriter->endElement();

        $objWriter->startElement('c:spPr');
        $fillColor = $legend->getFillColor();
        if ($fillColor->isUsable()) {
            $this->writeColor($objWriter, $fillColor);
        }
        $borderLines = $legend->getBorderLines();
        $this->writeLineStyles($objWriter, $borderLines);
        $this->writeEffects($objWriter, $borderLines);
        $objWriter->endElement(); 

        $legendText = $legend->getLegendText();
        $objWriter->startElement('c:txPr');
        $objWriter->startElement('a:bodyPr');
        $objWriter->endElement();

        $objWriter->startElement('a:lstStyle');
        $objWriter->endElement();

        $objWriter->startElement('a:p');
        $objWriter->startElement('a:pPr');
        $objWriter->writeAttribute('rtl', '0');

        $objWriter->startElement('a:defRPr');
        if ($legendText !== null) {
            $this->writeColor($objWriter, $legendText->getFillColorObject());
            $this->writeEffects($objWriter, $legendText);
        }
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->startElement('a:endParaRPr');
        $objWriter->writeAttribute('lang', 'en-US');
        $objWriter->endElement(); 

        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->endElement(); 
    }

    
    private function writePlotArea(XMLWriter $objWriter, ?PlotArea $plotArea, ?Title $xAxisLabel = null, ?Title $yAxisLabel = null, ?Axis $xAxis = null, ?Axis $yAxis = null): void
    {
        if ($plotArea === null) {
            return;
        }

        $id1 = $id2 = $id3 = '0';
        $this->seriesIndex = 0;
        $objWriter->startElement('c:plotArea');

        $layout = $plotArea->getLayout();

        $this->writeLayout($objWriter, $layout);

        $chartTypes = self::getChartType($plotArea);
        $catIsMultiLevelSeries = $valIsMultiLevelSeries = false;
        $plotGroupingType = '';
        $chartType = null;
        foreach ($chartTypes as $chartType) {
            $objWriter->startElement('c:' . $chartType);

            $groupCount = $plotArea->getPlotGroupCount();
            $plotGroup = null;
            for ($i = 0; $i < $groupCount; ++$i) {
                $plotGroup = $plotArea->getPlotGroupByIndex($i);
                $groupType = $plotGroup->getPlotType();
                if ($groupType == $chartType) {
                    $plotStyle = $plotGroup->getPlotStyle();
                    if (!empty($plotStyle) && $groupType === DataSeries::TYPE_RADARCHART) {
                        $objWriter->startElement('c:radarStyle');
                        $objWriter->writeAttribute('val', $plotStyle);
                        $objWriter->endElement();
                    } elseif (!empty($plotStyle) && $groupType === DataSeries::TYPE_SCATTERCHART) {
                        $objWriter->startElement('c:scatterStyle');
                        $objWriter->writeAttribute('val', $plotStyle);
                        $objWriter->endElement();
                    } elseif ($groupType === DataSeries::TYPE_SURFACECHART_3D || $groupType === DataSeries::TYPE_SURFACECHART) {
                        $objWriter->startElement('c:wireframe');
                        $objWriter->writeAttribute('val', $plotStyle ? '1' : '0');
                        $objWriter->endElement();
                    }

                    $this->writePlotGroup($plotGroup, $chartType, $objWriter, $catIsMultiLevelSeries, $valIsMultiLevelSeries, $plotGroupingType);
                }
            }

            $this->writeDataLabels($objWriter, $layout);

            if ($chartType === DataSeries::TYPE_LINECHART && $plotGroup) {
                
                $objWriter->startElement('c:smooth');
                $objWriter->writeAttribute('val', (string) (int) $plotGroup->getSmoothLine());
                $objWriter->endElement();
            } elseif (($chartType === DataSeries::TYPE_BARCHART) || ($chartType === DataSeries::TYPE_BARCHART_3D)) {
                $objWriter->startElement('c:gapWidth');
                $objWriter->writeAttribute('val', '150');
                $objWriter->endElement();

                if ($plotGroupingType == 'percentStacked' || $plotGroupingType == 'stacked') {
                    $objWriter->startElement('c:overlap');
                    $objWriter->writeAttribute('val', '100');
                    $objWriter->endElement();
                }
            } elseif ($chartType === DataSeries::TYPE_BUBBLECHART) {
                $scale = ($plotGroup === null) ? '' : (string) $plotGroup->getPlotStyle();
                if ($scale !== '') {
                    $objWriter->startElement('c:bubbleScale');
                    $objWriter->writeAttribute('val', $scale);
                    $objWriter->endElement();
                }

                $objWriter->startElement('c:showNegBubbles');
                $objWriter->writeAttribute('val', '0');
                $objWriter->endElement();
            } elseif ($chartType === DataSeries::TYPE_STOCKCHART) {
                $objWriter->startElement('c:hiLowLines');
                $objWriter->endElement();

                $gapWidth = $plotArea->getGapWidth();
                $upBars = $plotArea->getUseUpBars();
                $downBars = $plotArea->getUseDownBars();
                if ($gapWidth !== null || $upBars || $downBars) {
                    $objWriter->startElement('c:upDownBars');
                    if ($gapWidth !== null) {
                        $objWriter->startElement('c:gapWidth');
                        $objWriter->writeAttribute('val', "$gapWidth");
                        $objWriter->endElement();
                    }
                    if ($upBars) {
                        $objWriter->startElement('c:upBars');
                        $objWriter->endElement();
                    }
                    if ($downBars) {
                        $objWriter->startElement('c:downBars');
                        $objWriter->endElement();
                    }
                    $objWriter->endElement(); 
                }
            }

            
            $id1 = '110438656';
            $id2 = '110444544';
            $id3 = '110365312'; 

            if (($chartType !== DataSeries::TYPE_PIECHART) && ($chartType !== DataSeries::TYPE_PIECHART_3D) && ($chartType !== DataSeries::TYPE_DONUTCHART)) {
                $objWriter->startElement('c:axId');
                $objWriter->writeAttribute('val', $id1);
                $objWriter->endElement();
                $objWriter->startElement('c:axId');
                $objWriter->writeAttribute('val', $id2);
                $objWriter->endElement();
                if ($chartType === DataSeries::TYPE_SURFACECHART_3D || $chartType === DataSeries::TYPE_SURFACECHART) {
                    $objWriter->startElement('c:axId');
                    $objWriter->writeAttribute('val', $id3);
                    $objWriter->endElement();
                }
            } else {
                $objWriter->startElement('c:firstSliceAng');
                $objWriter->writeAttribute('val', '0');
                $objWriter->endElement();

                if ($chartType === DataSeries::TYPE_DONUTCHART) {
                    $objWriter->startElement('c:holeSize');
                    $objWriter->writeAttribute('val', '50');
                    $objWriter->endElement();
                }
            }

            $objWriter->endElement();
        }

        if (($chartType !== DataSeries::TYPE_PIECHART) && ($chartType !== DataSeries::TYPE_PIECHART_3D) && ($chartType !== DataSeries::TYPE_DONUTCHART)) {
            if ($chartType === DataSeries::TYPE_BUBBLECHART) {
                $this->writeValueAxis($objWriter, $xAxisLabel, $chartType, $id2, $id1, $catIsMultiLevelSeries, $xAxis ?? new Axis());
            } else {
                $this->writeCategoryAxis($objWriter, $xAxisLabel, $id1, $id2, $catIsMultiLevelSeries, $xAxis ?? new Axis());
            }

            $this->writeValueAxis($objWriter, $yAxisLabel, $chartType, $id1, $id2, $valIsMultiLevelSeries, $yAxis ?? new Axis());
            if ($chartType === DataSeries::TYPE_SURFACECHART_3D || $chartType === DataSeries::TYPE_SURFACECHART) {
                $this->writeSerAxis($objWriter, $id2, $id3);
            }
        }
        $stops = $plotArea->getGradientFillStops();
        if ($plotArea->getNoFill() || !empty($stops)) {
            $objWriter->startElement('c:spPr');
            if ($plotArea->getNoFill()) {
                $objWriter->startElement('a:noFill');
                $objWriter->endElement(); 
            }
            if (!empty($stops)) {
                $objWriter->startElement('a:gradFill');
                $objWriter->startElement('a:gsLst');
                foreach ($stops as $stop) {
                    $objWriter->startElement('a:gs');
                    $objWriter->writeAttribute('pos', (string) (Properties::PERCENTAGE_MULTIPLIER * (float) $stop[0]));
                    $this->writeColor($objWriter, $stop[1], false);
                    $objWriter->endElement(); 
                }
                $objWriter->endElement(); 
                $angle = $plotArea->getGradientFillAngle();
                if ($angle !== null) {
                    $objWriter->startElement('a:lin');
                    $objWriter->writeAttribute('ang', Properties::angleToXml($angle));
                    $objWriter->endElement(); 
                }
                $objWriter->endElement(); 
            }
            $objWriter->endElement(); 
        }

        $objWriter->endElement(); 
    }

    private function writeDataLabelsBool(XMLWriter $objWriter, string $name, ?bool $value): void
    {
        if ($value !== null) {
            $objWriter->startElement("c:$name");
            $objWriter->writeAttribute('val', $value ? '1' : '0');
            $objWriter->endElement();
        }
    }

    
    private function writeDataLabels(XMLWriter $objWriter, ?Layout $chartLayout = null): void
    {
        if (!isset($chartLayout)) {
            return;
        }
        $objWriter->startElement('c:dLbls');

        $fillColor = $chartLayout->getLabelFillColor();
        $borderColor = $chartLayout->getLabelBorderColor();
        if ($fillColor && $fillColor->isUsable()) {
            $objWriter->startElement('c:spPr');
            $this->writeColor($objWriter, $fillColor);
            if ($borderColor && $borderColor->isUsable()) {
                $objWriter->startElement('a:ln');
                $this->writeColor($objWriter, $borderColor);
                $objWriter->endElement(); 
            }
            $objWriter->endElement(); 
        }
        $labelFont = $chartLayout->getLabelFont();
        if ($labelFont !== null) {
            $objWriter->startElement('c:txPr');

            $objWriter->startElement('a:bodyPr');
            $objWriter->writeAttribute('wrap', 'square');
            $objWriter->writeAttribute('lIns', '38100');
            $objWriter->writeAttribute('tIns', '19050');
            $objWriter->writeAttribute('rIns', '38100');
            $objWriter->writeAttribute('bIns', '19050');
            $objWriter->writeAttribute('anchor', 'ctr');
            $objWriter->startElement('a:spAutoFit');
            $objWriter->endElement(); 
            $objWriter->endElement(); 

            $objWriter->startElement('a:lstStyle');
            $objWriter->endElement(); 
            $this->writeLabelFont($objWriter, $labelFont, $chartLayout->getLabelEffects());

            $objWriter->endElement(); 
        }

        if ($chartLayout->getNumFmtCode() !== '') {
            $objWriter->startElement('c:numFmt');
            $objWriter->writeAttribute('formatCode', $chartLayout->getnumFmtCode());
            $objWriter->writeAttribute('sourceLinked', (string) (int) $chartLayout->getnumFmtLinked());
            $objWriter->endElement(); 
        }
        if ($chartLayout->getDLblPos() !== '') {
            $objWriter->startElement('c:dLblPos');
            $objWriter->writeAttribute('val', $chartLayout->getDLblPos());
            $objWriter->endElement(); 
        }
        $this->writeDataLabelsBool($objWriter, 'showLegendKey', $chartLayout->getShowLegendKey());
        $this->writeDataLabelsBool($objWriter, 'showVal', $chartLayout->getShowVal());
        $this->writeDataLabelsBool($objWriter, 'showCatName', $chartLayout->getShowCatName());
        $this->writeDataLabelsBool($objWriter, 'showSerName', $chartLayout->getShowSerName());
        $this->writeDataLabelsBool($objWriter, 'showPercent', $chartLayout->getShowPercent());
        $this->writeDataLabelsBool($objWriter, 'showBubbleSize', $chartLayout->getShowBubbleSize());
        $this->writeDataLabelsBool($objWriter, 'showLeaderLines', $chartLayout->getShowLeaderLines());

        $objWriter->endElement(); 
    }

    
    private function writeCategoryAxis(XMLWriter $objWriter, ?Title $xAxisLabel, string $id1, string $id2, bool $isMultiLevelSeries, Axis $yAxis): void
    {
        
        
        $axisType = $yAxis->getAxisType();
        if ($axisType !== '') {
            $objWriter->startElement("c:$axisType");
        } elseif ($yAxis->getAxisIsNumericFormat()) {
            $objWriter->startElement('c:' . Axis::AXIS_TYPE_VALUE);
        } else {
            $objWriter->startElement('c:' . Axis::AXIS_TYPE_CATEGORY);
        }
        $majorGridlines = $yAxis->getMajorGridlines();
        $minorGridlines = $yAxis->getMinorGridlines();

        if ($id1 !== '0') {
            $objWriter->startElement('c:axId');
            $objWriter->writeAttribute('val', $id1);
            $objWriter->endElement();
        }

        $objWriter->startElement('c:scaling');
        if (is_numeric($yAxis->getAxisOptionsProperty('logBase'))) {
            $logBase = $yAxis->getAxisOptionsProperty('logBase') + 0;
            if ($logBase >= 2 && $logBase <= 1000) {
                $objWriter->startElement('c:logBase');
                $objWriter->writeAttribute('val', (string) $logBase);
                $objWriter->endElement();
            }
        }
        if ($yAxis->getAxisOptionsProperty('maximum') !== null) {
            $objWriter->startElement('c:max');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('maximum'));
            $objWriter->endElement();
        }
        if ($yAxis->getAxisOptionsProperty('minimum') !== null) {
            $objWriter->startElement('c:min');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('minimum'));
            $objWriter->endElement();
        }
        if (!empty($yAxis->getAxisOptionsProperty('orientation'))) {
            $objWriter->startElement('c:orientation');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('orientation'));
            $objWriter->endElement();
        }
        $objWriter->endElement(); 

        $objWriter->startElement('c:delete');
        $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('hidden') ?? '0');
        $objWriter->endElement();

        $objWriter->startElement('c:axPos');
        $objWriter->writeAttribute('val', 'b');
        $objWriter->endElement();

        if ($majorGridlines !== null) {
            $objWriter->startElement('c:majorGridlines');
            $objWriter->startElement('c:spPr');
            $this->writeLineStyles($objWriter, $majorGridlines);
            $this->writeEffects($objWriter, $majorGridlines);
            $objWriter->endElement(); 
            $objWriter->endElement(); 
        }

        if ($minorGridlines !== null && $minorGridlines->getObjectState()) {
            $objWriter->startElement('c:minorGridlines');
            $objWriter->startElement('c:spPr');
            $this->writeLineStyles($objWriter, $minorGridlines);
            $this->writeEffects($objWriter, $minorGridlines);
            $objWriter->endElement(); 
            $objWriter->endElement(); 
        }

        if ($xAxisLabel !== null) {
            $objWriter->startElement('c:title');
            $caption = $xAxisLabel->getCaption();
            $objWriter->startElement('c:tx');
            $objWriter->startElement('c:rich');

            $objWriter->startElement('a:bodyPr');
            $objWriter->endElement(); 

            $objWriter->startElement('a:lstStyle');
            $objWriter->endElement(); 

            $objWriter->startElement('a:p');

            if (is_array($caption)) {
                $caption = $caption[0];
            }
            $this->getParentWriter()->getWriterPartstringtable()->writeRichTextForCharts($objWriter, $caption, 'a');

            $objWriter->endElement(); 
            $objWriter->endElement(); 
            $objWriter->endElement(); 

            $layout = $xAxisLabel->getLayout();
            $this->writeLayout($objWriter, $layout);

            $objWriter->startElement('c:overlay');
            $objWriter->writeAttribute('val', '0');
            $objWriter->endElement(); 

            $objWriter->endElement(); 
        }

        $objWriter->startElement('c:numFmt');
        $objWriter->writeAttribute('formatCode', $yAxis->getAxisNumberFormat());
        $objWriter->writeAttribute('sourceLinked', $yAxis->getAxisNumberSourceLinked());
        $objWriter->endElement();

        if (!empty($yAxis->getAxisOptionsProperty('major_tick_mark'))) {
            $objWriter->startElement('c:majorTickMark');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('major_tick_mark'));
            $objWriter->endElement();
        }

        if (!empty($yAxis->getAxisOptionsProperty('minor_tick_mark'))) {
            $objWriter->startElement('c:minorTickMark');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('minor_tick_mark'));
            $objWriter->endElement();
        }

        if (!empty($yAxis->getAxisOptionsProperty('axis_labels'))) {
            $objWriter->startElement('c:tickLblPos');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('axis_labels'));
            $objWriter->endElement();
        }

        $textRotation = $yAxis->getAxisOptionsProperty('textRotation');
        $axisText = $yAxis->getAxisText();

        if ($axisText !== null || is_numeric($textRotation)) {
            $objWriter->startElement('c:txPr');
            $objWriter->startElement('a:bodyPr');
            if (is_numeric($textRotation)) {
                $objWriter->writeAttribute('rot', Properties::angleToXml((float) $textRotation));
            }
            $objWriter->endElement(); 
            $objWriter->startElement('a:lstStyle');
            $objWriter->endElement(); 
            $this->writeLabelFont($objWriter, ($axisText === null) ? null : $axisText->getFont(), $axisText);
            $objWriter->endElement(); 
        }

        $objWriter->startElement('c:spPr');
        $this->writeColor($objWriter, $yAxis->getFillColorObject());
        $this->writeLineStyles($objWriter, $yAxis, $yAxis->getNoFill());
        $this->writeEffects($objWriter, $yAxis);
        $objWriter->endElement(); 

        if ($yAxis->getAxisOptionsProperty('major_unit') !== null) {
            $objWriter->startElement('c:majorUnit');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('major_unit'));
            $objWriter->endElement();
        }

        if ($yAxis->getAxisOptionsProperty('minor_unit') !== null) {
            $objWriter->startElement('c:minorUnit');
            $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('minor_unit'));
            $objWriter->endElement();
        }

        if ($id2 !== '0') {
            $objWriter->startElement('c:crossAx');
            $objWriter->writeAttribute('val', $id2);
            $objWriter->endElement();

            if (!empty($yAxis->getAxisOptionsProperty('horizontal_crosses'))) {
                $objWriter->startElement('c:crosses');
                $objWriter->writeAttribute('val', $yAxis->getAxisOptionsProperty('horizontal_crosses'));
                $objWriter->endElement();
            }
        }

        $objWriter->startElement('c:auto');
        
        $objWriter->writeAttribute('val', ($axisType === Axis::AXIS_TYPE_DATE) ? '0' : '1');
        $objWriter->endElement();

        $objWriter->startElement('c:lblAlgn');
        $objWriter->writeAttribute('val', 'ctr');
        $objWriter->endElement();

        $objWriter->startElement('c:lblOffset');
        $objWriter->writeAttribute('val', '100');
        $objWriter->endElement();

        if ($axisType === Axis::AXIS_TYPE_DATE) {
            $property = 'baseTimeUnit';
            $propertyVal = $yAxis->getAxisOptionsProperty($property);
            if (!empty($propertyVal)) {
                $objWriter->startElement("c:$property");
                $objWriter->writeAttribute('val', $propertyVal);
                $objWriter->endElement();
            }
            $property = 'majorTimeUnit';
            $propertyVal = $yAxis->getAxisOptionsProperty($property);
            if (!empty($propertyVal)) {
                $objWriter->startElement("c:$property");
                $objWriter->writeAttribute('val', $propertyVal);
                $objWriter->endElement();
            }
            $property = 'minorTimeUnit';
            $propertyVal = $yAxis->getAxisOptionsProperty($property);
            if (!empty($propertyVal)) {
                $objWriter->startElement("c:$property");
                $objWriter->writeAttribute('val', $propertyVal);
                $objWriter->endElement();
            }
        }

        if ($isMultiLevelSeries) {
            $objWriter->startElement('c:noMultiLvlLbl');
            $objWriter->writeAttribute('val', '0');
            $objWriter->endElement();
        }
        $objWriter->endElement();
    }

    
    private function writeValueAxis(XMLWriter $objWriter, ?Title $yAxisLabel, ?string $groupType, string $id1, string $id2, bool $isMultiLevelSeries, Axis $xAxis): void
    {
        $objWriter->startElement('c:' . Axis::AXIS_TYPE_VALUE);
        $majorGridlines = $xAxis->getMajorGridlines();
        $minorGridlines = $xAxis->getMinorGridlines();

        if ($id2 !== '0') {
            $objWriter->startElement('c:axId');
            $objWriter->writeAttribute('val', $id2);
            $objWriter->endElement();
        }

        $objWriter->startElement('c:scaling');
        if (is_numeric($xAxis->getAxisOptionsProperty('logBase'))) {
            $logBase = $xAxis->getAxisOptionsProperty('logBase') + 0;
            if ($logBase >= 2 && $logBase <= 1000) {
                $objWriter->startElement('c:logBase');
                $objWriter->writeAttribute('val', (string) $logBase);
                $objWriter->endElement();
            }
        }

        if ($xAxis->getAxisOptionsProperty('maximum') !== null) {
            $objWriter->startElement('c:max');
            $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('maximum'));
            $objWriter->endElement();
        }

        if ($xAxis->getAxisOptionsProperty('minimum') !== null) {
            $objWriter->startElement('c:min');
            $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('minimum'));
            $objWriter->endElement();
        }

        if (!empty($xAxis->getAxisOptionsProperty('orientation'))) {
            $objWriter->startElement('c:orientation');
            $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('orientation'));
            $objWriter->endElement();
        }

        $objWriter->endElement(); 

        $objWriter->startElement('c:delete');
        $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('hidden') ?? '0');
        $objWriter->endElement();

        $objWriter->startElement('c:axPos');
        $objWriter->writeAttribute('val', 'l');
        $objWriter->endElement();

        if ($majorGridlines !== null) {
            $objWriter->startElement('c:majorGridlines');
            $objWriter->startElement('c:spPr');
            $this->writeLineStyles($objWriter, $majorGridlines);
            $this->writeEffects($objWriter, $majorGridlines);
            $objWriter->endElement(); 
            $objWriter->endElement(); 
        }

        if ($minorGridlines !== null && $minorGridlines->getObjectState()) {
            $objWriter->startElement('c:minorGridlines');
            $objWriter->startElement('c:spPr');
            $this->writeLineStyles($objWriter, $minorGridlines);
            $this->writeEffects($objWriter, $minorGridlines);
            $objWriter->endElement(); 
            $objWriter->endElement(); 
        }

        if ($yAxisLabel !== null) {
            $objWriter->startElement('c:title');
            $caption = $yAxisLabel->getCaption();
            $objWriter->startElement('c:tx');
            $objWriter->startElement('c:rich');

            $objWriter->startElement('a:bodyPr');
            $objWriter->endElement(); 

            $objWriter->startElement('a:lstStyle');
            $objWriter->endElement(); 

            $objWriter->startElement('a:p');

            if (is_array($caption)) {
                $caption = $caption[0];
            }
            $this->getParentWriter()
                ->getWriterPartstringtable()
                ->writeRichTextForCharts($objWriter, $caption, 'a');

            $objWriter->endElement(); 
            $objWriter->endElement(); 
            $objWriter->endElement(); 

            if ($groupType !== DataSeries::TYPE_BUBBLECHART) {
                $layout = $yAxisLabel->getLayout();
                $this->writeLayout($objWriter, $layout);
            }

            $objWriter->startElement('c:overlay');
            $objWriter->writeAttribute('val', '0');
            $objWriter->endElement(); 

            $objWriter->endElement(); 
        }

        $objWriter->startElement('c:numFmt');
        $objWriter->writeAttribute('formatCode', $xAxis->getAxisNumberFormat());
        $objWriter->writeAttribute('sourceLinked', $xAxis->getAxisNumberSourceLinked());
        $objWriter->endElement();

        if (!empty($xAxis->getAxisOptionsProperty('major_tick_mark'))) {
            $objWriter->startElement('c:majorTickMark');
            $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('major_tick_mark'));
            $objWriter->endElement();
        }

        if (!empty($xAxis->getAxisOptionsProperty('minor_tick_mark'))) {
            $objWriter->startElement('c:minorTickMark');
            $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('minor_tick_mark'));
            $objWriter->endElement();
        }

        if (!empty($xAxis->getAxisOptionsProperty('axis_labels'))) {
            $objWriter->startElement('c:tickLblPos');
            $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('axis_labels'));
            $objWriter->endElement();
        }

        $textRotation = $xAxis->getAxisOptionsProperty('textRotation');
        $axisText = $xAxis->getAxisText();

        if ($axisText !== null || is_numeric($textRotation)) {
            $objWriter->startElement('c:txPr');
            $objWriter->startElement('a:bodyPr');
            if (is_numeric($textRotation)) {
                $objWriter->writeAttribute('rot', Properties::angleToXml((float) $textRotation));
            }
            $objWriter->endElement(); 
            $objWriter->startElement('a:lstStyle');
            $objWriter->endElement(); 

            $this->writeLabelFont($objWriter, ($axisText === null) ? null : $axisText->getFont(), $axisText);

            $objWriter->endElement(); 
        }

        $objWriter->startElement('c:spPr');
        $this->writeColor($objWriter, $xAxis->getFillColorObject());
        $this->writeLineStyles($objWriter, $xAxis, $xAxis->getNoFill());
        $this->writeEffects($objWriter, $xAxis);
        $objWriter->endElement(); 

        if ($id1 !== '0') {
            $objWriter->startElement('c:crossAx');
            $objWriter->writeAttribute('val', $id1);
            $objWriter->endElement();

            if ($xAxis->getAxisOptionsProperty('horizontal_crosses_value') !== null) {
                $objWriter->startElement('c:crossesAt');
                $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('horizontal_crosses_value'));
                $objWriter->endElement();
            } else {
                $crosses = $xAxis->getAxisOptionsProperty('horizontal_crosses');
                if ($crosses) {
                    $objWriter->startElement('c:crosses');
                    $objWriter->writeAttribute('val', $crosses);
                    $objWriter->endElement();
                }
            }

            $crossBetween = $xAxis->getCrossBetween();
            if ($crossBetween !== '') {
                $objWriter->startElement('c:crossBetween');
                $objWriter->writeAttribute('val', $crossBetween);
                $objWriter->endElement();
            }

            if ($xAxis->getAxisType() === Axis::AXIS_TYPE_VALUE) {
                $dispUnits = $xAxis->getAxisOptionsProperty('dispUnitsBuiltIn');
                $dispUnits = ($dispUnits == Axis::TRILLION_INDEX) ? Axis::DISP_UNITS_TRILLIONS : (is_numeric($dispUnits) ? (Axis::DISP_UNITS_BUILTIN_INT[(int) $dispUnits] ?? '') : $dispUnits);
                if (in_array($dispUnits, Axis::DISP_UNITS_BUILTIN_INT, true)) {
                    $objWriter->startElement('c:dispUnits');
                    $objWriter->startElement('c:builtInUnit');
                    $objWriter->writeAttribute('val', $dispUnits);
                    $objWriter->endElement(); 
                    if ($xAxis->getDispUnitsTitle() !== null) {
                        
                        $objWriter->writeElement('c:dispUnitsLbl');
                    }
                    $objWriter->endElement(); 
                }
            }

            if ($xAxis->getAxisOptionsProperty('major_unit') !== null) {
                $objWriter->startElement('c:majorUnit');
                $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('major_unit'));
                $objWriter->endElement();
            }

            if ($xAxis->getAxisOptionsProperty('minor_unit') !== null) {
                $objWriter->startElement('c:minorUnit');
                $objWriter->writeAttribute('val', $xAxis->getAxisOptionsProperty('minor_unit'));
                $objWriter->endElement();
            }
        }

        if ($isMultiLevelSeries) {
            if ($groupType !== DataSeries::TYPE_BUBBLECHART) {
                $objWriter->startElement('c:noMultiLvlLbl');
                $objWriter->writeAttribute('val', '0');
                $objWriter->endElement();
            }
        }

        $objWriter->endElement();
    }

    
    private function writeSerAxis(XMLWriter $objWriter, string $id2, string $id3): void
    {
        $objWriter->startElement('c:serAx');

        $objWriter->startElement('c:axId');
        $objWriter->writeAttribute('val', $id3);
        $objWriter->endElement(); 

        $objWriter->startElement('c:scaling');
        $objWriter->startElement('c:orientation');
        $objWriter->writeAttribute('val', 'minMax');
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->startElement('c:delete');
        $objWriter->writeAttribute('val', '0');
        $objWriter->endElement(); 

        $objWriter->startElement('c:axPos');
        $objWriter->writeAttribute('val', 'b');
        $objWriter->endElement(); 

        $objWriter->startElement('c:majorTickMark');
        $objWriter->writeAttribute('val', 'out');
        $objWriter->endElement(); 

        $objWriter->startElement('c:minorTickMark');
        $objWriter->writeAttribute('val', 'none');
        $objWriter->endElement(); 

        $objWriter->startElement('c:tickLblPos');
        $objWriter->writeAttribute('val', 'nextTo');
        $objWriter->endElement(); 

        $objWriter->startElement('c:crossAx');
        $objWriter->writeAttribute('val', $id2);
        $objWriter->endElement(); 

        $objWriter->startElement('c:crosses');
        $objWriter->writeAttribute('val', 'autoZero');
        $objWriter->endElement(); 

        $objWriter->endElement(); 
    }

    
    private static function getChartType(PlotArea $plotArea): array
    {
        $groupCount = $plotArea->getPlotGroupCount();

        if ($groupCount == 1) {
            $plotType = $plotArea->getPlotGroupByIndex(0)->getPlotType();
            $chartType = ($plotType === null) ? [] : [$plotType];
        } else {
            $chartTypes = [];
            for ($i = 0; $i < $groupCount; ++$i) {
                $plotType = $plotArea->getPlotGroupByIndex($i)->getPlotType();
                if ($plotType !== null) {
                    $chartTypes[] = $plotType;
                }
            }
            $chartType = array_unique($chartTypes);
        }
        if (count($chartType) == 0) {
            throw new WriterException('Chart is not yet implemented');
        }

        return $chartType;
    }

    
    private function writePlotSeriesValuesElement(XMLWriter $objWriter, int $val, ?ChartColor $fillColor): void
    {
        if ($fillColor === null || !$fillColor->isUsable()) {
            return;
        }
        $objWriter->startElement('c:dPt');

        $objWriter->startElement('c:idx');
        $objWriter->writeAttribute('val', "$val");
        $objWriter->endElement(); 

        $objWriter->startElement('c:spPr');
        $this->writeColor($objWriter, $fillColor);
        $objWriter->endElement(); 

        $objWriter->endElement(); 
    }

    
    private function writePlotGroup(?DataSeries $plotGroup, string $groupType, XMLWriter $objWriter, bool &$catIsMultiLevelSeries, bool &$valIsMultiLevelSeries, string &$plotGroupingType): void
    {
        if ($plotGroup === null) {
            return;
        }

        if (($groupType == DataSeries::TYPE_BARCHART) || ($groupType == DataSeries::TYPE_BARCHART_3D)) {
            $objWriter->startElement('c:barDir');
            $objWriter->writeAttribute('val', $plotGroup->getPlotDirection());
            $objWriter->endElement();
        }

        $plotGroupingType = (string) $plotGroup->getPlotGrouping();
        if ($plotGroupingType !== '' && $groupType !== DataSeries::TYPE_SURFACECHART && $groupType !== DataSeries::TYPE_SURFACECHART_3D) {
            $objWriter->startElement('c:grouping');
            $objWriter->writeAttribute('val', $plotGroupingType);
            $objWriter->endElement();
        }

        
        $plotSeriesOrder = $plotGroup->getPlotOrder();
        $plotSeriesCount = count($plotSeriesOrder);

        if (($groupType !== DataSeries::TYPE_RADARCHART) && ($groupType !== DataSeries::TYPE_STOCKCHART)) {
            if ($groupType !== DataSeries::TYPE_LINECHART) {
                if (($groupType == DataSeries::TYPE_PIECHART) || ($groupType == DataSeries::TYPE_PIECHART_3D) || ($groupType == DataSeries::TYPE_DONUTCHART) || ($plotSeriesCount > 1)) {
                    $objWriter->startElement('c:varyColors');
                    $objWriter->writeAttribute('val', '1');
                    $objWriter->endElement();
                } else {
                    $objWriter->startElement('c:varyColors');
                    $objWriter->writeAttribute('val', '0');
                    $objWriter->endElement();
                }
            }
        }

        $plotSeriesIdx = 0;
        foreach ($plotSeriesOrder as $plotSeriesIdx => $plotSeriesRef) {
            $objWriter->startElement('c:ser');

            $objWriter->startElement('c:idx');
            $adder = array_key_exists(0, $plotSeriesOrder) ? $this->seriesIndex : 0;
            $objWriter->writeAttribute('val', (string) ($adder + $plotSeriesIdx));
            $objWriter->endElement();

            $objWriter->startElement('c:order');
            $objWriter->writeAttribute('val', (string) ($adder + $plotSeriesRef));
            $objWriter->endElement();

            $plotLabel = $plotGroup->getPlotLabelByIndex($plotSeriesIdx);
            $labelFill = null;
            if ($plotLabel && $groupType === DataSeries::TYPE_LINECHART) {
                $labelFill = $plotLabel->getFillColorObject();
                $labelFill = ($labelFill instanceof ChartColor) ? $labelFill : null;
            }

            
            $plotSeriesValues = $plotGroup->getPlotValuesByIndex($plotSeriesIdx);

            if ($plotSeriesValues !== false && in_array($groupType, self::CUSTOM_COLOR_TYPES, true)) {
                $fillColorValues = $plotSeriesValues->getFillColorObject();
                if ($fillColorValues !== null && is_array($fillColorValues)) {
                    foreach (($plotSeriesValues->getDataValues() ?? []) as $dataKey => $dataValue) {
                        $this->writePlotSeriesValuesElement($objWriter, $dataKey, $fillColorValues[$dataKey] ?? null);
                    }
                }
            }
            if ($plotSeriesValues !== false && $plotSeriesValues->getLabelLayout()) {
                $this->writeDataLabels($objWriter, $plotSeriesValues->getLabelLayout());
            }

            
            $plotSeriesLabel = $plotGroup->getPlotLabelByIndex($plotSeriesIdx);
            if ($plotSeriesLabel && ($plotSeriesLabel->getPointCount() > 0)) {
                $objWriter->startElement('c:tx');
                $objWriter->startElement('c:strRef');
                $this->writePlotSeriesLabel($plotSeriesLabel, $objWriter);
                $objWriter->endElement();
                $objWriter->endElement();
            }

            
            if (
                $plotSeriesValues !== false
            ) {
                $objWriter->startElement('c:spPr');
                if ($plotLabel && $groupType !== DataSeries::TYPE_LINECHART) {
                    $fillColor = $plotLabel->getFillColorObject();
                    if ($fillColor !== null && !is_array($fillColor) && $fillColor->isUsable()) {
                        $this->writeColor($objWriter, $fillColor);
                    }
                }
                $fillObject = $labelFill ?? $plotSeriesValues->getFillColorObject();
                $callLineStyles = true;
                if ($fillObject instanceof ChartColor && $fillObject->isUsable()) {
                    if ($groupType === DataSeries::TYPE_LINECHART) {
                        $objWriter->startElement('a:ln');
                        $callLineStyles = false;
                    }
                    $this->writeColor($objWriter, $fillObject);
                    if (!$callLineStyles) {
                        $objWriter->endElement(); 
                    }
                }
                $nofill = $groupType === DataSeries::TYPE_STOCKCHART || (($groupType === DataSeries::TYPE_SCATTERCHART || $groupType === DataSeries::TYPE_LINECHART) && !$plotSeriesValues->getScatterLines());
                if ($callLineStyles) {
                    $this->writeLineStyles($objWriter, $plotSeriesValues, $nofill);
                    $this->writeEffects($objWriter, $plotSeriesValues);
                }
                $objWriter->endElement(); 
            }

            if ($plotSeriesValues) {
                $plotSeriesMarker = $plotSeriesValues->getPointMarker();
                $markerFillColor = $plotSeriesValues->getMarkerFillColor();
                $fillUsed = $markerFillColor->IsUsable();
                $markerBorderColor = $plotSeriesValues->getMarkerBorderColor();
                $borderUsed = $markerBorderColor->isUsable();
                if ($plotSeriesMarker || $fillUsed || $borderUsed) {
                    $objWriter->startElement('c:marker');
                    $objWriter->startElement('c:symbol');
                    if ($plotSeriesMarker) {
                        $objWriter->writeAttribute('val', $plotSeriesMarker);
                    }
                    $objWriter->endElement();

                    if ($plotSeriesMarker !== 'none') {
                        $objWriter->startElement('c:size');
                        $objWriter->writeAttribute('val', (string) $plotSeriesValues->getPointSize());
                        $objWriter->endElement(); 
                        $objWriter->startElement('c:spPr');
                        $this->writeColor($objWriter, $markerFillColor);
                        if ($borderUsed) {
                            $objWriter->startElement('a:ln');
                            $this->writeColor($objWriter, $markerBorderColor);
                            $objWriter->endElement(); 
                        }
                        $objWriter->endElement(); 
                    }

                    $objWriter->endElement();
                }
            }

            if (($groupType === DataSeries::TYPE_BARCHART) || ($groupType === DataSeries::TYPE_BARCHART_3D) || ($groupType === DataSeries::TYPE_BUBBLECHART)) {
                $objWriter->startElement('c:invertIfNegative');
                $objWriter->writeAttribute('val', '0');
                $objWriter->endElement();
            }
            
            if ($plotSeriesValues !== false) {
                foreach ($plotSeriesValues->getTrendLines() as $trendLine) {
                    $trendLineType = $trendLine->getTrendLineType();
                    $order = $trendLine->getOrder();
                    $period = $trendLine->getPeriod();
                    $dispRSqr = $trendLine->getDispRSqr();
                    $dispEq = $trendLine->getDispEq();
                    $forward = $trendLine->getForward();
                    $backward = $trendLine->getBackward();
                    $intercept = $trendLine->getIntercept();
                    $name = $trendLine->getName();
                    $trendLineColor = $trendLine->getLineColor(); 

                    $objWriter->startElement('c:trendline'); 
                    if ($name !== '') {
                        $objWriter->startElement('c:name');
                        $objWriter->writeRawData($name);
                        $objWriter->endElement(); 
                    }
                    $objWriter->startElement('c:spPr');

                    if (!$trendLineColor->isUsable()) {
                        
                        $dsvLineColor = $plotSeriesValues->getLineColor();
                        if ($dsvLineColor->isUsable()) {
                            $trendLine
                                ->getLineColor()
                                ->setColorProperties($dsvLineColor->getValue(), $dsvLineColor->getAlpha(), $dsvLineColor->getType());
                        }
                    } 

                    $this->writeLineStyles($objWriter, $trendLine, false); 

                    $objWriter->endElement(); 

                    $objWriter->startElement('c:trendlineType'); 
                    $objWriter->writeAttribute('val', $trendLineType);
                    $objWriter->endElement(); 
                    if ($backward !== 0.0) {
                        $objWriter->startElement('c:backward');
                        $objWriter->writeAttribute('val', "$backward");
                        $objWriter->endElement(); 
                    }
                    if ($forward !== 0.0) {
                        $objWriter->startElement('c:forward');
                        $objWriter->writeAttribute('val', "$forward");
                        $objWriter->endElement(); 
                    }
                    if ($intercept !== 0.0) {
                        $objWriter->startElement('c:intercept');
                        $objWriter->writeAttribute('val', "$intercept");
                        $objWriter->endElement(); 
                    }
                    if ($trendLineType == TrendLine::TRENDLINE_POLYNOMIAL) {
                        $objWriter->startElement('c:order');
                        $objWriter->writeAttribute('val', "$order");
                        $objWriter->endElement(); 
                    }
                    if ($trendLineType == TrendLine::TRENDLINE_MOVING_AVG) {
                        $objWriter->startElement('c:period');
                        $objWriter->writeAttribute('val', "$period");
                        $objWriter->endElement(); 
                    }
                    $objWriter->startElement('c:dispRSqr');
                    $objWriter->writeAttribute('val', $dispRSqr ? '1' : '0');
                    $objWriter->endElement();
                    $objWriter->startElement('c:dispEq');
                    $objWriter->writeAttribute('val', $dispEq ? '1' : '0');
                    $objWriter->endElement();
                    if ($groupType === DataSeries::TYPE_SCATTERCHART || $groupType === DataSeries::TYPE_LINECHART) {
                        $objWriter->startElement('c:trendlineLbl');
                        $objWriter->startElement('c:numFmt');
                        $objWriter->writeAttribute('formatCode', 'General');
                        $objWriter->writeAttribute('sourceLinked', '0');
                        $objWriter->endElement();  
                        $objWriter->endElement();  
                    }

                    $objWriter->endElement(); 
                }
            }

            
            $plotSeriesCategory = $plotGroup->getPlotCategoryByIndex($plotSeriesIdx);
            if ($plotSeriesCategory && ($plotSeriesCategory->getPointCount() > 0)) {
                $catIsMultiLevelSeries = $catIsMultiLevelSeries || $plotSeriesCategory->isMultiLevelSeries();

                if (($groupType == DataSeries::TYPE_PIECHART) || ($groupType == DataSeries::TYPE_PIECHART_3D) || ($groupType == DataSeries::TYPE_DONUTCHART)) {
                    $plotStyle = $plotGroup->getPlotStyle();
                    if (is_numeric($plotStyle)) {
                        $objWriter->startElement('c:explosion');
                        $objWriter->writeAttribute('val', $plotStyle);
                        $objWriter->endElement();
                    }
                }

                if (($groupType === DataSeries::TYPE_BUBBLECHART) || ($groupType === DataSeries::TYPE_SCATTERCHART)) {
                    $objWriter->startElement('c:xVal');
                } else {
                    $objWriter->startElement('c:cat');
                }

                
                
                $CategoryDatatype = $plotSeriesCategory->getDataType();
                if ($CategoryDatatype == DataSeriesValues::DATASERIES_TYPE_NUMBER) {
                    $this->writePlotSeriesValues($plotSeriesCategory, $objWriter, $groupType, 'num');
                } else {
                    $this->writePlotSeriesValues($plotSeriesCategory, $objWriter, $groupType, 'str');
                }
                $objWriter->endElement();
            }

            
            if ($plotSeriesValues) {
                $valIsMultiLevelSeries = $valIsMultiLevelSeries || $plotSeriesValues->isMultiLevelSeries();

                if (($groupType === DataSeries::TYPE_BUBBLECHART) || ($groupType === DataSeries::TYPE_SCATTERCHART)) {
                    $objWriter->startElement('c:yVal');
                } else {
                    $objWriter->startElement('c:val');
                }

                $this->writePlotSeriesValues($plotSeriesValues, $objWriter, $groupType, 'num');
                $objWriter->endElement();
                if ($groupType === DataSeries::TYPE_SCATTERCHART && $plotGroup->getPlotStyle() === 'smoothMarker') {
                    $objWriter->startElement('c:smooth');
                    $objWriter->writeAttribute('val', $plotSeriesValues->getSmoothLine() ? '1' : '0');
                    $objWriter->endElement();
                }
            }

            if ($groupType === DataSeries::TYPE_BUBBLECHART) {
                if (!empty($plotGroup->getPlotBubbleSizes()[$plotSeriesIdx])) {
                    $objWriter->startElement('c:bubbleSize');
                    $this->writePlotSeriesValues(
                        $plotGroup->getPlotBubbleSizes()[$plotSeriesIdx],
                        $objWriter,
                        $groupType,
                        'num'
                    );
                    $objWriter->endElement();
                    if ($plotSeriesValues !== false) {
                        $objWriter->startElement('c:bubble3D');
                        $objWriter->writeAttribute('val', $plotSeriesValues->getBubble3D() ? '1' : '0');
                        $objWriter->endElement();
                    }
                } elseif ($plotSeriesValues !== false) {
                    $this->writeBubbles($plotSeriesValues, $objWriter);
                }
            }

            $objWriter->endElement();
        }

        $this->seriesIndex += $plotSeriesIdx + 1;
    }

    
    private function writePlotSeriesLabel(?DataSeriesValues $plotSeriesLabel, XMLWriter $objWriter): void
    {
        if ($plotSeriesLabel === null) {
            return;
        }

        $objWriter->startElement('c:f');
        $objWriter->writeRawData($plotSeriesLabel->getDataSource());
        $objWriter->endElement();

        $objWriter->startElement('c:strCache');
        $objWriter->startElement('c:ptCount');
        $objWriter->writeAttribute('val', (string) $plotSeriesLabel->getPointCount());
        $objWriter->endElement();

        foreach (($plotSeriesLabel->getDataValues() ?? []) as $plotLabelKey => $plotLabelValue) {
            
            $objWriter->startElement('c:pt');
            $objWriter->writeAttribute('idx', $plotLabelKey);

            $objWriter->startElement('c:v');
            $objWriter->writeRawData($plotLabelValue);
            $objWriter->endElement();
            $objWriter->endElement();
        }
        $objWriter->endElement();
    }

    
    private function writePlotSeriesValues(?DataSeriesValues $plotSeriesValues, XMLWriter $objWriter, string $groupType, string $dataType = 'str'): void
    {
        if ($plotSeriesValues === null) {
            return;
        }

        if ($plotSeriesValues->isMultiLevelSeries()) {
            $levelCount = $plotSeriesValues->multiLevelCount();

            $objWriter->startElement('c:multiLvlStrRef');

            $objWriter->startElement('c:f');
            $objWriter->writeRawData($plotSeriesValues->getDataSource());
            $objWriter->endElement();

            $objWriter->startElement('c:multiLvlStrCache');

            $objWriter->startElement('c:ptCount');
            $objWriter->writeAttribute('val', (string) $plotSeriesValues->getPointCount());
            $objWriter->endElement();

            for ($level = 0; $level < $levelCount; ++$level) {
                $objWriter->startElement('c:lvl');

                foreach (($plotSeriesValues->getDataValues() ?? []) as $plotSeriesKey => $plotSeriesValue) {
                    
                    if (isset($plotSeriesValue[$level])) {
                        $objWriter->startElement('c:pt');
                        $objWriter->writeAttribute('idx', $plotSeriesKey);

                        $objWriter->startElement('c:v');
                        $objWriter->writeRawData($plotSeriesValue[$level]);
                        $objWriter->endElement();
                        $objWriter->endElement();
                    }
                }

                $objWriter->endElement();
            }

            $objWriter->endElement();

            $objWriter->endElement();
        } else {
            $objWriter->startElement('c:' . $dataType . 'Ref');

            $objWriter->startElement('c:f');
            $objWriter->writeRawData($plotSeriesValues->getDataSource());
            $objWriter->endElement();

            $count = $plotSeriesValues->getPointCount();
            $source = $plotSeriesValues->getDataSource();
            $values = $plotSeriesValues->getDataValues();
            if ($count > 1 || ($count === 1 && is_array($values) && array_key_exists(0, $values) && "=$source" !== StringHelper::convertToString($values[0], false))) {
                $objWriter->startElement('c:' . $dataType . 'Cache');

                if (($groupType != DataSeries::TYPE_PIECHART) && ($groupType != DataSeries::TYPE_PIECHART_3D) && ($groupType != DataSeries::TYPE_DONUTCHART)) {
                    if (($plotSeriesValues->getFormatCode() !== null) && ($plotSeriesValues->getFormatCode() !== '')) {
                        $objWriter->startElement('c:formatCode');
                        $objWriter->writeRawData($plotSeriesValues->getFormatCode());
                        $objWriter->endElement();
                    }
                }

                $objWriter->startElement('c:ptCount');
                $objWriter->writeAttribute('val', (string) $plotSeriesValues->getPointCount());
                $objWriter->endElement();

                
                $dataValues = $plotSeriesValues->getDataValues();
                if (!empty($dataValues)) {
                    foreach ($dataValues as $plotSeriesKey => $plotSeriesValue) {
                        $objWriter->startElement('c:pt');
                        $objWriter->writeAttribute('idx', $plotSeriesKey);

                        $objWriter->startElement('c:v');
                        $objWriter->writeRawData($plotSeriesValue);
                        $objWriter->endElement();
                        $objWriter->endElement();
                    }
                }

                $objWriter->endElement(); 
            }

            $objWriter->endElement(); 
        }
    }

    private const CUSTOM_COLOR_TYPES = [
        DataSeries::TYPE_BARCHART,
        DataSeries::TYPE_BARCHART_3D,
        DataSeries::TYPE_PIECHART,
        DataSeries::TYPE_PIECHART_3D,
        DataSeries::TYPE_DONUTCHART,
    ];

    
    private function writeBubbles(?DataSeriesValues $plotSeriesValues, XMLWriter $objWriter): void
    {
        if ($plotSeriesValues === null) {
            return;
        }

        $objWriter->startElement('c:bubbleSize');
        $objWriter->startElement('c:numLit');

        $objWriter->startElement('c:formatCode');
        $objWriter->writeRawData('General');
        $objWriter->endElement();

        $objWriter->startElement('c:ptCount');
        $objWriter->writeAttribute('val', (string) $plotSeriesValues->getPointCount());
        $objWriter->endElement();

        $dataValues = $plotSeriesValues->getDataValues();
        if (!empty($dataValues)) {
            foreach ($dataValues as $plotSeriesKey => $plotSeriesValue) {
                $objWriter->startElement('c:pt');
                $objWriter->writeAttribute('idx', $plotSeriesKey);
                $objWriter->startElement('c:v');
                $objWriter->writeRawData('1');
                $objWriter->endElement();
                $objWriter->endElement();
            }
        }

        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('c:bubble3D');
        $objWriter->writeAttribute('val', $plotSeriesValues->getBubble3D() ? '1' : '0');
        $objWriter->endElement();
    }

    
    private function writeLayout(XMLWriter $objWriter, ?Layout $layout = null): void
    {
        $objWriter->startElement('c:layout');

        if ($layout !== null) {
            $objWriter->startElement('c:manualLayout');

            $layoutTarget = $layout->getLayoutTarget();
            if ($layoutTarget !== null) {
                $objWriter->startElement('c:layoutTarget');
                $objWriter->writeAttribute('val', $layoutTarget);
                $objWriter->endElement();
            }

            $xMode = $layout->getXMode();
            if ($xMode !== null) {
                $objWriter->startElement('c:xMode');
                $objWriter->writeAttribute('val', $xMode);
                $objWriter->endElement();
            }

            $yMode = $layout->getYMode();
            if ($yMode !== null) {
                $objWriter->startElement('c:yMode');
                $objWriter->writeAttribute('val', $yMode);
                $objWriter->endElement();
            }

            $x = $layout->getXPosition();
            if ($x !== null) {
                $objWriter->startElement('c:x');
                $objWriter->writeAttribute('val', "$x");
                $objWriter->endElement();
            }

            $y = $layout->getYPosition();
            if ($y !== null) {
                $objWriter->startElement('c:y');
                $objWriter->writeAttribute('val', "$y");
                $objWriter->endElement();
            }

            $w = $layout->getWidth();
            if ($w !== null) {
                $objWriter->startElement('c:w');
                $objWriter->writeAttribute('val', "$w");
                $objWriter->endElement();
            }

            $h = $layout->getHeight();
            if ($h !== null) {
                $objWriter->startElement('c:h');
                $objWriter->writeAttribute('val', "$h");
                $objWriter->endElement();
            }

            $objWriter->endElement();
        }

        $objWriter->endElement();
    }

    
    private function writeAlternateContent(XMLWriter $objWriter): void
    {
        $objWriter->startElement('mc:AlternateContent');
        $objWriter->writeAttribute('xmlns:mc', Namespaces::COMPATIBILITY);

        $objWriter->startElement('mc:Choice');
        $objWriter->writeAttribute('Requires', 'c14');
        $objWriter->writeAttribute('xmlns:c14', Namespaces::CHART_ALTERNATE);

        $objWriter->startElement('c14:style');
        $objWriter->writeAttribute('val', '102');
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('mc:Fallback');
        $objWriter->startElement('c:style');
        $objWriter->writeAttribute('val', '2');
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->endElement();
    }

    
    private function writePrintSettings(XMLWriter $objWriter): void
    {
        $objWriter->startElement('c:printSettings');

        $objWriter->startElement('c:headerFooter');
        $objWriter->endElement();

        $objWriter->startElement('c:pageMargins');
        $objWriter->writeAttribute('footer', '0.3');
        $objWriter->writeAttribute('header', '0.3');
        $objWriter->writeAttribute('r', '0.7');
        $objWriter->writeAttribute('l', '0.7');
        $objWriter->writeAttribute('t', '0.75');
        $objWriter->writeAttribute('b', '0.75');
        $objWriter->endElement();

        $objWriter->startElement('c:pageSetup');
        $objWriter->writeAttribute('orientation', 'portrait');
        $objWriter->endElement();

        $objWriter->endElement();
    }

    private function writeEffects(XMLWriter $objWriter, Properties $yAxis): void
    {
        if (
            !empty($yAxis->getSoftEdgesSize())
            || !empty($yAxis->getShadowProperty('effect'))
            || !empty($yAxis->getGlowProperty('size'))
        ) {
            $objWriter->startElement('a:effectLst');
            $this->writeGlow($objWriter, $yAxis);
            $this->writeShadow($objWriter, $yAxis);
            $this->writeSoftEdge($objWriter, $yAxis);
            $objWriter->endElement(); 
        }
    }

    private function writeShadow(XMLWriter $objWriter, Properties $xAxis): void
    {
        if (empty($xAxis->getShadowProperty('effect'))) {
            return;
        }
        
        $effect = $xAxis->getShadowProperty('effect');
        $objWriter->startElement("a:$effect");

        if (is_numeric($xAxis->getShadowProperty('blur'))) {
            $objWriter->writeAttribute('blurRad', Properties::pointsToXml((float) $xAxis->getShadowProperty('blur')));
        }
        if (is_numeric($xAxis->getShadowProperty('distance'))) {
            $objWriter->writeAttribute('dist', Properties::pointsToXml((float) $xAxis->getShadowProperty('distance')));
        }
        if (is_numeric($xAxis->getShadowProperty('direction'))) {
            $objWriter->writeAttribute('dir', Properties::angleToXml((float) $xAxis->getShadowProperty('direction')));
        }
        $algn = $xAxis->getShadowProperty('algn');
        if (is_string($algn) && $algn !== '') {
            $objWriter->writeAttribute('algn', $algn);
        }
        foreach (['sx', 'sy'] as $sizeType) {
            $sizeValue = $xAxis->getShadowProperty(['size', $sizeType]);
            if (is_numeric($sizeValue)) {
                $objWriter->writeAttribute($sizeType, Properties::tenthOfPercentToXml((float) $sizeValue));
            }
        }
        foreach (['kx', 'ky'] as $sizeType) {
            $sizeValue = $xAxis->getShadowProperty(['size', $sizeType]);
            if (is_numeric($sizeValue)) {
                $objWriter->writeAttribute($sizeType, Properties::angleToXml((float) $sizeValue));
            }
        }
        $rotWithShape = $xAxis->getShadowProperty('rotWithShape');
        if (is_numeric($rotWithShape)) {
            $objWriter->writeAttribute('rotWithShape', (string) (int) $rotWithShape);
        }

        $this->writeColor($objWriter, $xAxis->getShadowColorObject(), false);

        $objWriter->endElement();
    }

    private function writeGlow(XMLWriter $objWriter, Properties $yAxis): void
    {
        $size = $yAxis->getGlowProperty('size');
        if (empty($size)) {
            return;
        }
        $objWriter->startElement('a:glow');
        $objWriter->writeAttribute('rad', Properties::pointsToXml((float) $size));
        $this->writeColor($objWriter, $yAxis->getGlowColorObject(), false);
        $objWriter->endElement(); 
    }

    private function writeSoftEdge(XMLWriter $objWriter, Properties $yAxis): void
    {
        $softEdgeSize = $yAxis->getSoftEdgesSize();
        if (empty($softEdgeSize)) {
            return;
        }
        $objWriter->startElement('a:softEdge');
        $objWriter->writeAttribute('rad', Properties::pointsToXml((float) $softEdgeSize));
        $objWriter->endElement(); 
    }

    private function writeLineStyles(XMLWriter $objWriter, Properties $gridlines, bool $noFill = false): void
    {
        $objWriter->startElement('a:ln');
        $widthTemp = $gridlines->getLineStyleProperty('width');
        if (is_numeric($widthTemp)) {
            $objWriter->writeAttribute('w', Properties::pointsToXml((float) $widthTemp));
        }
        $this->writeNotEmpty($objWriter, 'cap', $gridlines->getLineStyleProperty('cap'));
        $this->writeNotEmpty($objWriter, 'cmpd', $gridlines->getLineStyleProperty('compound'));
        if ($noFill) {
            $objWriter->startElement('a:noFill');
            $objWriter->endElement();
        } else {
            $this->writeColor($objWriter, $gridlines->getLineColor());
        }

        $dash = $gridlines->getLineStyleProperty('dash');
        if (!empty($dash)) {
            $objWriter->startElement('a:prstDash');
            $this->writeNotEmpty($objWriter, 'val', $dash);
            $objWriter->endElement();
        }

        if ($gridlines->getLineStyleProperty('join') === 'miter') {
            $objWriter->startElement('a:miter');
            $objWriter->writeAttribute('lim', '800000');
            $objWriter->endElement();
        } elseif ($gridlines->getLineStyleProperty('join') === 'bevel') {
            $objWriter->startElement('a:bevel');
            $objWriter->endElement();
        }

        if ($gridlines->getLineStyleProperty(['arrow', 'head', 'type'])) {
            $objWriter->startElement('a:headEnd');
            $objWriter->writeAttribute('type', $gridlines->getLineStyleProperty(['arrow', 'head', 'type']));
            $this->writeNotEmpty($objWriter, 'w', $gridlines->getLineStyleArrowWidth('head'));
            $this->writeNotEmpty($objWriter, 'len', $gridlines->getLineStyleArrowLength('head'));
            $objWriter->endElement();
        }

        if ($gridlines->getLineStyleProperty(['arrow', 'end', 'type'])) {
            $objWriter->startElement('a:tailEnd');
            $objWriter->writeAttribute('type', $gridlines->getLineStyleProperty(['arrow', 'end', 'type']));
            $this->writeNotEmpty($objWriter, 'w', $gridlines->getLineStyleArrowWidth('end'));
            $this->writeNotEmpty($objWriter, 'len', $gridlines->getLineStyleArrowLength('end'));
            $objWriter->endElement();
        }
        $objWriter->endElement(); 
    }

    private function writeNotEmpty(XMLWriter $objWriter, string $name, ?string $value): void
    {
        if ($value !== null && $value !== '') {
            $objWriter->writeAttribute($name, $value);
        }
    }

    private function writeColor(XMLWriter $objWriter, ChartColor $chartColor, bool $solidFill = true): void
    {
        $type = $chartColor->getType();
        $value = $chartColor->getValue();
        if (!empty($type) && !empty($value)) {
            if ($solidFill) {
                $objWriter->startElement('a:solidFill');
            }
            $objWriter->startElement("a:$type");
            $objWriter->writeAttribute('val', $value);
            $alpha = $chartColor->getAlpha();
            if (is_numeric($alpha)) {
                $objWriter->startElement('a:alpha');
                $objWriter->writeAttribute('val', ChartColor::alphaToXml((int) $alpha));
                $objWriter->endElement(); 
            }
            $brightness = $chartColor->getBrightness();
            if (is_numeric($brightness)) {
                $brightness = (int) $brightness;
                $lumOff = 100 - $brightness;
                $objWriter->startElement('a:lumMod');
                $objWriter->writeAttribute('val', ChartColor::alphaToXml($brightness));
                $objWriter->endElement(); 
                $objWriter->startElement('a:lumOff');
                $objWriter->writeAttribute('val', ChartColor::alphaToXml($lumOff));
                $objWriter->endElement(); 
            }
            $objWriter->endElement(); 
            if ($solidFill) {
                $objWriter->endElement(); 
            }
        }
    }

    private function writeLabelFont(XMLWriter $objWriter, ?Font $labelFont, ?Properties $axisText): void
    {
        $objWriter->startElement('a:p');
        $objWriter->startElement('a:pPr');
        $objWriter->startElement('a:defRPr');
        if ($labelFont !== null) {
            $fontSize = $labelFont->getSize();
            if (is_numeric($fontSize)) {
                $fontSize *= (($fontSize < 100) ? 100 : 1);
                $objWriter->writeAttribute('sz', (string) $fontSize);
            }
            if ($labelFont->getBold() === true) {
                $objWriter->writeAttribute('b', '1');
            }
            if ($labelFont->getItalic() === true) {
                $objWriter->writeAttribute('i', '1');
            }
            $cap = $labelFont->getCap();
            if ($cap !== null) {
                $objWriter->writeAttribute('cap', $cap);
            }
            $fontColor = $labelFont->getChartColor();
            if ($fontColor !== null) {
                $this->writeColor($objWriter, $fontColor);
            }
        }
        if ($axisText !== null) {
            $this->writeEffects($objWriter, $axisText);
        }
        if ($labelFont !== null) {
            $defaultFont = ($labelFont->getName() !== Font::DEFAULT_FONT_NAME) ? $labelFont->getName() : '';
            $fontName = $labelFont->getLatin() ?: $defaultFont;
            if (!empty($fontName)) {
                $objWriter->startElement('a:latin');
                $objWriter->writeAttribute('typeface', $fontName);
                $objWriter->endElement();
            }
            $fontName = $labelFont->getEastAsian() ?: $defaultFont;
            if (!empty($fontName)) {
                $objWriter->startElement('a:eastAsian');
                $objWriter->writeAttribute('typeface', $fontName);
                $objWriter->endElement();
            }
            $fontName = $labelFont->getComplexScript() ?: $defaultFont;
            if (!empty($fontName)) {
                $objWriter->startElement('a:complexScript');
                $objWriter->writeAttribute('typeface', $fontName);
                $objWriter->endElement();
            }
        }
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 
    }
}
