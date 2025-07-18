<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class SheetView
{
    
    const SHEETVIEW_NORMAL = 'normal';
    const SHEETVIEW_PAGE_LAYOUT = 'pageLayout';
    const SHEETVIEW_PAGE_BREAK_PREVIEW = 'pageBreakPreview';

    private const SHEET_VIEW_TYPES = [
        self::SHEETVIEW_NORMAL,
        self::SHEETVIEW_PAGE_LAYOUT,
        self::SHEETVIEW_PAGE_BREAK_PREVIEW,
    ];

    
    private ?int $zoomScale = 100;

    
    private ?int $zoomScaleNormal = 100;

    
    private int $zoomScalePageLayoutView = 100;

    
    private int $zoomScaleSheetLayoutView = 100;

    
    private bool $showZeros = true;

    
    private string $sheetviewType = self::SHEETVIEW_NORMAL;

    
    public function __construct()
    {
    }

    
    public function getZoomScale(): ?int
    {
        return $this->zoomScale;
    }

    
    public function setZoomScale(?int $zoomScale): static
    {
        
        
        if ($zoomScale === null || $zoomScale >= 1) {
            $this->zoomScale = $zoomScale;
        } else {
            throw new PhpSpreadsheetException('Scale must be greater than or equal to 1.');
        }

        return $this;
    }

    
    public function getZoomScaleNormal(): ?int
    {
        return $this->zoomScaleNormal;
    }

    
    public function setZoomScaleNormal(?int $zoomScaleNormal): static
    {
        if ($zoomScaleNormal === null || $zoomScaleNormal >= 1) {
            $this->zoomScaleNormal = $zoomScaleNormal;
        } else {
            throw new PhpSpreadsheetException('Scale must be greater than or equal to 1.');
        }

        return $this;
    }

    public function getZoomScalePageLayoutView(): int
    {
        return $this->zoomScalePageLayoutView;
    }

    public function setZoomScalePageLayoutView(int $zoomScalePageLayoutView): static
    {
        if ($zoomScalePageLayoutView >= 1) {
            $this->zoomScalePageLayoutView = $zoomScalePageLayoutView;
        } else {
            throw new PhpSpreadsheetException('Scale must be greater than or equal to 1.');
        }

        return $this;
    }

    public function getZoomScaleSheetLayoutView(): int
    {
        return $this->zoomScaleSheetLayoutView;
    }

    public function setZoomScaleSheetLayoutView(int $zoomScaleSheetLayoutView): static
    {
        if ($zoomScaleSheetLayoutView >= 1) {
            $this->zoomScaleSheetLayoutView = $zoomScaleSheetLayoutView;
        } else {
            throw new PhpSpreadsheetException('Scale must be greater than or equal to 1.');
        }

        return $this;
    }

    
    public function setShowZeros(bool $showZeros): void
    {
        $this->showZeros = $showZeros;
    }

    public function getShowZeros(): bool
    {
        return $this->showZeros;
    }

    
    public function getView(): string
    {
        return $this->sheetviewType;
    }

    
    public function setView(?string $sheetViewType): static
    {
        
        if ($sheetViewType === null) {
            $sheetViewType = self::SHEETVIEW_NORMAL;
        }
        if (in_array($sheetViewType, self::SHEET_VIEW_TYPES)) {
            $this->sheetviewType = $sheetViewType;
        } else {
            throw new PhpSpreadsheetException('Invalid sheetview layout type.');
        }

        return $this;
    }
}
