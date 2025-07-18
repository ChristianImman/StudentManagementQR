<?php

namespace PhpOffice\PhpSpreadsheet\Chart\Renderer;

use AccBarPlot;
use AccLinePlot;
use BarPlot;
use ContourPlot;
use Graph;
use GroupBarPlot;
use LinePlot;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PieGraph;
use PiePlot;
use PiePlot3D;
use PiePlotC;
use RadarGraph;
use RadarPlot;
use ScatterPlot;
use Spline;
use StockPlot;


abstract class JpGraphRendererBase implements IRenderer
{
    private const DEFAULT_WIDTH = 640.0;

    private const DEFAULT_HEIGHT = 480.0;

    private static $colourSet = [
        'mediumpurple1', 'palegreen3', 'gold1', 'cadetblue1',
        'darkmagenta', 'coral', 'dodgerblue3', 'eggplant',
        'mediumblue', 'magenta', 'sandybrown', 'cyan',
        'firebrick1', 'forestgreen', 'deeppink4', 'darkolivegreen',
        'goldenrod2',
    ];

    private static array $markSet;

    private Chart $chart;

    private $graph;

    private static $plotColour = 0;

    private static $plotMark = 0;

    
    public function __construct(Chart $chart)
    {
        static::init();
        $this->graph = null;
        $this->chart = $chart;

        self::$markSet = [
            'diamond' => MARK_DIAMOND,
            'square' => MARK_SQUARE,
            'triangle' => MARK_UTRIANGLE,
            'x' => MARK_X,
            'star' => MARK_STAR,
            'dot' => MARK_FILLEDCIRCLE,
            'dash' => MARK_DTRIANGLE,
            'circle' => MARK_CIRCLE,
            'plus' => MARK_CROSS,
        ];
    }

    private function getGraphWidth(): float
    {
        return $this->chart->getRenderedWidth() ?? self::DEFAULT_WIDTH;
    }

    private function getGraphHeight(): float
    {
        return $this->chart->getRenderedHeight() ?? self::DEFAULT_HEIGHT;
    }

    
    abstract protected static function init(): void;

    private function formatPointMarker($seriesPlot, $markerID)
    {
        $plotMarkKeys = array_keys(self::$markSet);
        if ($markerID === null) {
            
            self::$plotMark %= count(self::$markSet);
            $seriesPlot->mark->SetType(self::$markSet[$plotMarkKeys[self::$plotMark++]]);
        } elseif ($markerID !== 'none') {
            
            if (isset(self::$markSet[$markerID])) {
                $seriesPlot->mark->SetType(self::$markSet[$markerID]);
            } else {
                
                self::$plotMark %= count(self::$markSet);
                $seriesPlot->mark->SetType(self::$markSet[$plotMarkKeys[self::$plotMark++]]);
            }
        } else {
            
            $seriesPlot->mark->Hide();
        }
        $seriesPlot->mark->SetColor(self::$colourSet[self::$plotColour]);
        $seriesPlot->mark->SetFillColor(self::$colourSet[self::$plotColour]);
        $seriesPlot->SetColor(self::$colourSet[self::$plotColour++]);

        return $seriesPlot;
    }

    private function formatDataSetLabels(int $groupID, array $datasetLabels, $rotation = '')
    {
        $datasetLabelFormatCode = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex(0)->getFormatCode() ?? '';
        
        $datasetLabelFormatCode = stripslashes($datasetLabelFormatCode);

        $testCurrentIndex = 0;
        foreach ($datasetLabels as $i => $datasetLabel) {
            if (is_array($datasetLabel)) {
                if ($rotation == 'bar') {
                    $datasetLabels[$i] = implode(' ', $datasetLabel);
                } else {
                    $datasetLabel = array_reverse($datasetLabel);
                    $datasetLabels[$i] = implode("\n", $datasetLabel);
                }
            } else {
                
                if ($datasetLabelFormatCode !== null) {
                    $datasetLabels[$i] = NumberFormat::toFormattedString($datasetLabel, $datasetLabelFormatCode);
                }
            }
            ++$testCurrentIndex;
        }

        return $datasetLabels;
    }

    private function percentageSumCalculation(int $groupID, $seriesCount)
    {
        $sumValues = [];
        
        for ($i = 0; $i < $seriesCount; ++$i) {
            if ($i == 0) {
                $sumValues = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getDataValues();
            } else {
                $nextValues = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getDataValues();
                foreach ($nextValues as $k => $value) {
                    if (isset($sumValues[$k])) {
                        $sumValues[$k] += $value;
                    } else {
                        $sumValues[$k] = $value;
                    }
                }
            }
        }

        return $sumValues;
    }

    private function percentageAdjustValues(array $dataValues, array $sumValues)
    {
        foreach ($dataValues as $k => $dataValue) {
            $dataValues[$k] = $dataValue / $sumValues[$k] * 100;
        }

        return $dataValues;
    }

    private function getCaption($captionElement)
    {
        
        $caption = ($captionElement !== null) ? $captionElement->getCaption() : null;
        
        if ($caption !== null) {
            
            if (is_array($caption)) {
                
                $caption = implode('', $caption);
            }
        }

        return $caption;
    }

    private function renderTitle(): void
    {
        $title = $this->getCaption($this->chart->getTitle());
        if ($title !== null) {
            $this->graph->title->Set($title);
        }
    }

    private function renderLegend(): void
    {
        $legend = $this->chart->getLegend();
        if ($legend !== null) {
            $legendPosition = $legend->getPosition();
            switch ($legendPosition) {
                case 'r':
                    $this->graph->legend->SetPos(0.01, 0.5, 'right', 'center'); 
                    $this->graph->legend->SetColumns(1);

                    break;
                case 'l':
                    $this->graph->legend->SetPos(0.01, 0.5, 'left', 'center'); 
                    $this->graph->legend->SetColumns(1);

                    break;
                case 't':
                    $this->graph->legend->SetPos(0.5, 0.01, 'center', 'top'); 

                    break;
                case 'b':
                    $this->graph->legend->SetPos(0.5, 0.99, 'center', 'bottom'); 

                    break;
                default:
                    $this->graph->legend->SetPos(0.01, 0.01, 'right', 'top'); 
                    $this->graph->legend->SetColumns(1);

                    break;
            }
        } else {
            $this->graph->legend->Hide();
        }
    }

    private function renderCartesianPlotArea(string $type = 'textlin'): void
    {
        $this->graph = new Graph($this->getGraphWidth(), $this->getGraphHeight());
        $this->graph->SetScale($type);

        $this->renderTitle();

        
        $rotation = $this->chart->getPlotArea()->getPlotGroupByIndex(0)->getPlotDirection();
        $reverse = $rotation == 'bar';

        $xAxisLabel = $this->chart->getXAxisLabel();
        if ($xAxisLabel !== null) {
            $title = $this->getCaption($xAxisLabel);
            if ($title !== null) {
                $this->graph->xaxis->SetTitle($title, 'center');
                $this->graph->xaxis->title->SetMargin(35);
                if ($reverse) {
                    $this->graph->xaxis->title->SetAngle(90);
                    $this->graph->xaxis->title->SetMargin(90);
                }
            }
        }

        $yAxisLabel = $this->chart->getYAxisLabel();
        if ($yAxisLabel !== null) {
            $title = $this->getCaption($yAxisLabel);
            if ($title !== null) {
                $this->graph->yaxis->SetTitle($title, 'center');
                if ($reverse) {
                    $this->graph->yaxis->title->SetAngle(0);
                    $this->graph->yaxis->title->SetMargin(-55);
                }
            }
        }
    }

    private function renderPiePlotArea(): void
    {
        $this->graph = new PieGraph($this->getGraphWidth(), $this->getGraphHeight());

        $this->renderTitle();
    }

    private function renderRadarPlotArea(): void
    {
        $this->graph = new RadarGraph($this->getGraphWidth(), $this->getGraphHeight());
        $this->graph->SetScale('lin');

        $this->renderTitle();
    }

    private function getDataLabel(int $groupId, int $index): mixed
    {
        $plotLabel = $this->chart->getPlotArea()->getPlotGroupByIndex($groupId)->getPlotLabelByIndex($index);
        if (!$plotLabel) {
            return '';
        }

        return $plotLabel->getDataValue();
    }

    private function renderPlotLine(int $groupID, bool $filled = false, bool $combination = false): void
    {
        $grouping = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotGrouping();

        $index = array_keys($this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotOrder())[0];
        $labelCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($index)->getPointCount();
        if ($labelCount > 0) {
            $datasetLabels = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex(0)->getDataValues();
            $datasetLabels = $this->formatDataSetLabels($groupID, $datasetLabels);
            $this->graph->xaxis->SetTickLabels($datasetLabels);
        }

        $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();
        $seriesPlots = [];
        if ($grouping == 'percentStacked') {
            $sumValues = $this->percentageSumCalculation($groupID, $seriesCount);
        } else {
            $sumValues = [];
        }

        
        for ($i = 0; $i < $seriesCount; ++$i) {
            $index = array_keys($this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotOrder())[$i];
            $dataValues = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($index)->getDataValues();
            $marker = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($index)->getPointMarker();

            if ($grouping == 'percentStacked') {
                $dataValues = $this->percentageAdjustValues($dataValues, $sumValues);
            }

            
            $testCurrentIndex = 0;
            foreach ($dataValues as $k => $dataValue) {
                while ($k != $testCurrentIndex) {
                    $dataValues[$testCurrentIndex] = null;
                    ++$testCurrentIndex;
                }
                ++$testCurrentIndex;
            }

            $seriesPlot = new LinePlot($dataValues);
            if ($combination) {
                $seriesPlot->SetBarCenter();
            }

            if ($filled) {
                $seriesPlot->SetFilled(true);
                $seriesPlot->SetColor('black');
                $seriesPlot->SetFillColor(self::$colourSet[self::$plotColour++]);
            } else {
                
                $this->formatPointMarker($seriesPlot, $marker);
            }

            $seriesPlot->SetLegend($this->getDataLabel($groupID, $index));

            $seriesPlots[] = $seriesPlot;
        }

        if ($grouping == 'standard') {
            $groupPlot = $seriesPlots;
        } else {
            $groupPlot = new AccLinePlot($seriesPlots);
        }
        $this->graph->Add($groupPlot);
    }

    private function renderPlotBar(int $groupID, ?string $dimensions = '2d'): void
    {
        $rotation = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotDirection();
        
        if (($groupID == 0) && ($rotation == 'bar')) {
            $this->graph->Set90AndMargin();
        }
        $grouping = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotGrouping();

        $index = array_keys($this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotOrder())[0];
        $labelCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($index)->getPointCount();
        if ($labelCount > 0) {
            $datasetLabels = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex(0)->getDataValues();
            $datasetLabels = $this->formatDataSetLabels($groupID, $datasetLabels, $rotation);
            
            if ($rotation == 'bar') {
                $datasetLabels = array_reverse($datasetLabels);
                $this->graph->yaxis->SetPos('max');
                $this->graph->yaxis->SetLabelAlign('center', 'top');
                $this->graph->yaxis->SetLabelSide(SIDE_RIGHT);
            }
            $this->graph->xaxis->SetTickLabels($datasetLabels);
        }

        $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();
        $seriesPlots = [];
        if ($grouping == 'percentStacked') {
            $sumValues = $this->percentageSumCalculation($groupID, $seriesCount);
        } else {
            $sumValues = [];
        }

        
        for ($j = 0; $j < $seriesCount; ++$j) {
            $index = array_keys($this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotOrder())[$j];
            $dataValues = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($index)->getDataValues();
            if ($grouping == 'percentStacked') {
                $dataValues = $this->percentageAdjustValues($dataValues, $sumValues);
            }

            
            $testCurrentIndex = 0;
            foreach ($dataValues as $k => $dataValue) {
                while ($k != $testCurrentIndex) {
                    $dataValues[$testCurrentIndex] = null;
                    ++$testCurrentIndex;
                }
                ++$testCurrentIndex;
            }

            
            if ($rotation == 'bar') {
                $dataValues = array_reverse($dataValues);
            }
            $seriesPlot = new BarPlot($dataValues);
            $seriesPlot->SetColor('black');
            $seriesPlot->SetFillColor(self::$colourSet[self::$plotColour++]);
            if ($dimensions == '3d') {
                $seriesPlot->SetShadow();
            }

            $seriesPlot->SetLegend($this->getDataLabel($groupID, $j));

            $seriesPlots[] = $seriesPlot;
        }
        
        if (($rotation == 'bar') && ($grouping != 'percentStacked')) {
            $seriesPlots = array_reverse($seriesPlots);
        }

        if ($grouping == 'clustered') {
            $groupPlot = new GroupBarPlot($seriesPlots);
        } elseif ($grouping == 'standard') {
            $groupPlot = new GroupBarPlot($seriesPlots);
        } else {
            $groupPlot = new AccBarPlot($seriesPlots);
            if ($dimensions == '3d') {
                $groupPlot->SetShadow();
            }
        }

        $this->graph->Add($groupPlot);
    }

    private function renderPlotScatter(int $groupID, bool $bubble): void
    {
        $scatterStyle = $bubbleSize = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotStyle();

        $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();

        
        for ($i = 0; $i < $seriesCount; ++$i) {
            $plotCategoryByIndex = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex($i);
            if ($plotCategoryByIndex === false) {
                $plotCategoryByIndex = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex(0);
            }
            $dataValuesY = $plotCategoryByIndex->getDataValues();
            $dataValuesX = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getDataValues();

            $redoDataValuesY = true;
            if ($bubble) {
                if (!$bubbleSize) {
                    $bubbleSize = '10';
                }
                $redoDataValuesY = false;
                foreach ($dataValuesY as $dataValueY) {
                    if (!is_int($dataValueY) && !is_float($dataValueY)) {
                        $redoDataValuesY = true;

                        break;
                    }
                }
            }
            if ($redoDataValuesY) {
                foreach ($dataValuesY as $k => $dataValueY) {
                    $dataValuesY[$k] = $k;
                }
            }

            $seriesPlot = new ScatterPlot($dataValuesX, $dataValuesY);
            if ($scatterStyle == 'lineMarker') {
                $seriesPlot->SetLinkPoints();
                $seriesPlot->link->SetColor(self::$colourSet[self::$plotColour]);
            } elseif ($scatterStyle == 'smoothMarker') {
                $spline = new Spline($dataValuesY, $dataValuesX);
                [$splineDataY, $splineDataX] = $spline->Get(count($dataValuesX) * $this->getGraphWidth() / 20);
                $lplot = new LinePlot($splineDataX, $splineDataY);
                $lplot->SetColor(self::$colourSet[self::$plotColour]);

                $this->graph->Add($lplot);
            }

            if ($bubble) {
                $this->formatPointMarker($seriesPlot, 'dot');
                $seriesPlot->mark->SetColor('black');
                $seriesPlot->mark->SetSize($bubbleSize);
            } else {
                $marker = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getPointMarker();
                $this->formatPointMarker($seriesPlot, $marker);
            }
            $seriesPlot->SetLegend($this->getDataLabel($groupID, $i));

            $this->graph->Add($seriesPlot);
        }
    }

    private function renderPlotRadar(int $groupID): void
    {
        $radarStyle = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotStyle();

        $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();

        
        for ($i = 0; $i < $seriesCount; ++$i) {
            $dataValuesY = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex($i)->getDataValues();
            $dataValuesX = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getDataValues();
            $marker = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getPointMarker();

            $dataValues = [];
            foreach ($dataValuesY as $k => $dataValueY) {
                $dataValues[$k] = is_array($dataValueY) ? implode(' ', array_reverse($dataValueY)) : $dataValueY;
            }
            $tmp = array_shift($dataValues);
            $dataValues[] = $tmp;
            $tmp = array_shift($dataValuesX);
            $dataValuesX[] = $tmp;

            $this->graph->SetTitles(array_reverse($dataValues));

            $seriesPlot = new RadarPlot(array_reverse($dataValuesX));

            $seriesPlot->SetColor(self::$colourSet[self::$plotColour++]);
            if ($radarStyle == 'filled') {
                $seriesPlot->SetFillColor(self::$colourSet[self::$plotColour]);
            }
            $this->formatPointMarker($seriesPlot, $marker);
            $seriesPlot->SetLegend($this->getDataLabel($groupID, $i));

            $this->graph->Add($seriesPlot);
        }
    }

    private function renderPlotContour(int $groupID): void
    {
        $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();

        $dataValues = [];
        
        for ($i = 0; $i < $seriesCount; ++$i) {
            $dataValuesX = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($i)->getDataValues();

            $dataValues[$i] = $dataValuesX;
        }
        $seriesPlot = new ContourPlot($dataValues);

        $this->graph->Add($seriesPlot);
    }

    private function renderPlotStock(int $groupID): void
    {
        $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();
        $plotOrder = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotOrder();

        $dataValues = [];
        
        foreach ($plotOrder as $i => $v) {
            $dataValuesX = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($v);
            if ($dataValuesX === false) {
                continue;
            }
            $dataValuesX = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($v)->getDataValues();
            foreach ($dataValuesX as $j => $dataValueX) {
                $dataValues[$plotOrder[$i]][$j] = $dataValueX;
            }
        }
        if (empty($dataValues)) {
            return;
        }

        $dataValuesPlot = [];
        
        $jMax = count($dataValues[0]);
        for ($j = 0; $j < $jMax; ++$j) {
            for ($i = 0; $i < $seriesCount; ++$i) {
                $dataValuesPlot[] = $dataValues[$i][$j] ?? null;
            }
        }

        
        $labelCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex(0)->getPointCount();
        if ($labelCount > 0) {
            $datasetLabels = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex(0)->getDataValues();
            $datasetLabels = $this->formatDataSetLabels($groupID, $datasetLabels);
            $this->graph->xaxis->SetTickLabels($datasetLabels);
        }

        $seriesPlot = new StockPlot($dataValuesPlot);
        $seriesPlot->SetWidth(20);

        $this->graph->Add($seriesPlot);
    }

    private function renderAreaChart($groupCount): void
    {
        $this->renderCartesianPlotArea();

        for ($i = 0; $i < $groupCount; ++$i) {
            $this->renderPlotLine($i, true, false);
        }
    }

    private function renderLineChart($groupCount): void
    {
        $this->renderCartesianPlotArea();

        for ($i = 0; $i < $groupCount; ++$i) {
            $this->renderPlotLine($i, false, false);
        }
    }

    private function renderBarChart($groupCount, ?string $dimensions = '2d'): void
    {
        $this->renderCartesianPlotArea();

        for ($i = 0; $i < $groupCount; ++$i) {
            $this->renderPlotBar($i, $dimensions);
        }
    }

    private function renderScatterChart($groupCount): void
    {
        $this->renderCartesianPlotArea('linlin');

        for ($i = 0; $i < $groupCount; ++$i) {
            $this->renderPlotScatter($i, false);
        }
    }

    private function renderBubbleChart($groupCount): void
    {
        $this->renderCartesianPlotArea('linlin');

        for ($i = 0; $i < $groupCount; ++$i) {
            $this->renderPlotScatter($i, true);
        }
    }

    private function renderPieChart($groupCount, ?string $dimensions = '2d', bool $doughnut = false, bool $multiplePlots = false): void
    {
        $this->renderPiePlotArea();

        $iLimit = ($multiplePlots) ? $groupCount : 1;
        for ($groupID = 0; $groupID < $iLimit; ++$groupID) {
            $exploded = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotStyle();
            $datasetLabels = [];
            if ($groupID == 0) {
                $labelCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex(0)->getPointCount();
                if ($labelCount > 0) {
                    $datasetLabels = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotCategoryByIndex(0)->getDataValues();
                    $datasetLabels = $this->formatDataSetLabels($groupID, $datasetLabels);
                }
            }

            $seriesCount = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotSeriesCount();
            
            $jLimit = ($multiplePlots) ? $seriesCount : 1;
            
            for ($j = 0; $j < $jLimit; ++$j) {
                $dataValues = $this->chart->getPlotArea()->getPlotGroupByIndex($groupID)->getPlotValuesByIndex($j)->getDataValues();

                
                $testCurrentIndex = 0;
                foreach ($dataValues as $k => $dataValue) {
                    while ($k != $testCurrentIndex) {
                        $dataValues[$testCurrentIndex] = null;
                        ++$testCurrentIndex;
                    }
                    ++$testCurrentIndex;
                }

                if ($dimensions == '3d') {
                    $seriesPlot = new PiePlot3D($dataValues);
                } else {
                    if ($doughnut) {
                        $seriesPlot = new PiePlotC($dataValues);
                    } else {
                        $seriesPlot = new PiePlot($dataValues);
                    }
                }

                if ($multiplePlots) {
                    $seriesPlot->SetSize(($jLimit - $j) / ($jLimit * 4));
                }

                if ($doughnut && method_exists($seriesPlot, 'SetMidColor')) {
                    $seriesPlot->SetMidColor('white');
                }

                $seriesPlot->SetColor(self::$colourSet[self::$plotColour++]);
                if (count($datasetLabels) > 0) {
                    $seriesPlot->SetLabels(array_fill(0, count($datasetLabels), ''));
                }
                if ($dimensions != '3d') {
                    $seriesPlot->SetGuideLines(false);
                }
                if ($j == 0) {
                    if ($exploded) {
                        $seriesPlot->ExplodeAll();
                    }
                    $seriesPlot->SetLegends($datasetLabels);
                }

                $this->graph->Add($seriesPlot);
            }
        }
    }

    private function renderRadarChart($groupCount): void
    {
        $this->renderRadarPlotArea();

        for ($groupID = 0; $groupID < $groupCount; ++$groupID) {
            $this->renderPlotRadar($groupID);
        }
    }

    private function renderStockChart($groupCount): void
    {
        $this->renderCartesianPlotArea('intint');

        for ($groupID = 0; $groupID < $groupCount; ++$groupID) {
            $this->renderPlotStock($groupID);
        }
    }

    private function renderContourChart($groupCount): void
    {
        $this->renderCartesianPlotArea('intint');

        for ($i = 0; $i < $groupCount; ++$i) {
            $this->renderPlotContour($i);
        }
    }

    private function renderCombinationChart($groupCount, $outputDestination): bool
    {
        $this->renderCartesianPlotArea();

        for ($i = 0; $i < $groupCount; ++$i) {
            $dimensions = null;
            $chartType = $this->chart->getPlotArea()->getPlotGroupByIndex($i)->getPlotType();
            switch ($chartType) {
                case 'area3DChart':
                case 'areaChart':
                    $this->renderPlotLine($i, true, true);

                    break;
                case 'bar3DChart':
                    $dimensions = '3d';
                    
                case 'barChart':
                    $this->renderPlotBar($i, $dimensions);

                    break;
                case 'line3DChart':
                case 'lineChart':
                    $this->renderPlotLine($i, false, true);

                    break;
                case 'scatterChart':
                    $this->renderPlotScatter($i, false);

                    break;
                case 'bubbleChart':
                    $this->renderPlotScatter($i, true);

                    break;
                default:
                    $this->graph = null;

                    return false;
            }
        }

        $this->renderLegend();

        $this->graph->Stroke($outputDestination);

        return true;
    }

    public function render(?string $outputDestination): bool
    {
        self::$plotColour = 0;

        $groupCount = $this->chart->getPlotArea()->getPlotGroupCount();

        $dimensions = null;
        if ($groupCount == 1) {
            $chartType = $this->chart->getPlotArea()->getPlotGroupByIndex(0)->getPlotType();
        } else {
            $chartTypes = [];
            for ($i = 0; $i < $groupCount; ++$i) {
                $chartTypes[] = $this->chart->getPlotArea()->getPlotGroupByIndex($i)->getPlotType();
            }
            $chartTypes = array_unique($chartTypes);
            if (count($chartTypes) == 1) {
                $chartType = array_pop($chartTypes);
            } elseif (count($chartTypes) == 0) {
                echo 'Chart is not yet implemented<br />';

                return false;
            } else {
                return $this->renderCombinationChart($groupCount, $outputDestination);
            }
        }

        switch ($chartType) {
            case 'area3DChart':
                $dimensions = '3d';
                
            case 'areaChart':
                $this->renderAreaChart($groupCount);

                break;
            case 'bar3DChart':
                $dimensions = '3d';
                
            case 'barChart':
                $this->renderBarChart($groupCount, $dimensions);

                break;
            case 'line3DChart':
                $dimensions = '3d';
                
            case 'lineChart':
                $this->renderLineChart($groupCount);

                break;
            case 'pie3DChart':
                $dimensions = '3d';
                
            case 'pieChart':
                $this->renderPieChart($groupCount, $dimensions, false, false);

                break;
            case 'doughnut3DChart':
                $dimensions = '3d';
                
            case 'doughnutChart':
                $this->renderPieChart($groupCount, $dimensions, true, true);

                break;
            case 'scatterChart':
                $this->renderScatterChart($groupCount);

                break;
            case 'bubbleChart':
                $this->renderBubbleChart($groupCount);

                break;
            case 'radarChart':
                $this->renderRadarChart($groupCount);

                break;
            case 'surface3DChart':
            case 'surfaceChart':
                $this->renderContourChart($groupCount);

                break;
            case 'stockChart':
                $this->renderStockChart($groupCount);

                break;
            default:
                echo $chartType . ' is not yet implemented<br />';

                return false;
        }
        $this->renderLegend();

        $this->graph->Stroke($outputDestination);

        return true;
    }
}
