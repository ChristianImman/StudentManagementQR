<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet\Table;

use PhpOffice\PhpSpreadsheet\Worksheet\Table;

class TableStyle
{
    const TABLE_STYLE_NONE = '';
    const TABLE_STYLE_LIGHT1 = 'TableStyleLight1';
    const TABLE_STYLE_LIGHT2 = 'TableStyleLight2';
    const TABLE_STYLE_LIGHT3 = 'TableStyleLight3';
    const TABLE_STYLE_LIGHT4 = 'TableStyleLight4';
    const TABLE_STYLE_LIGHT5 = 'TableStyleLight5';
    const TABLE_STYLE_LIGHT6 = 'TableStyleLight6';
    const TABLE_STYLE_LIGHT7 = 'TableStyleLight7';
    const TABLE_STYLE_LIGHT8 = 'TableStyleLight8';
    const TABLE_STYLE_LIGHT9 = 'TableStyleLight9';
    const TABLE_STYLE_LIGHT10 = 'TableStyleLight10';
    const TABLE_STYLE_LIGHT11 = 'TableStyleLight11';
    const TABLE_STYLE_LIGHT12 = 'TableStyleLight12';
    const TABLE_STYLE_LIGHT13 = 'TableStyleLight13';
    const TABLE_STYLE_LIGHT14 = 'TableStyleLight14';
    const TABLE_STYLE_LIGHT15 = 'TableStyleLight15';
    const TABLE_STYLE_LIGHT16 = 'TableStyleLight16';
    const TABLE_STYLE_LIGHT17 = 'TableStyleLight17';
    const TABLE_STYLE_LIGHT18 = 'TableStyleLight18';
    const TABLE_STYLE_LIGHT19 = 'TableStyleLight19';
    const TABLE_STYLE_LIGHT20 = 'TableStyleLight20';
    const TABLE_STYLE_LIGHT21 = 'TableStyleLight21';
    const TABLE_STYLE_MEDIUM1 = 'TableStyleMedium1';
    const TABLE_STYLE_MEDIUM2 = 'TableStyleMedium2';
    const TABLE_STYLE_MEDIUM3 = 'TableStyleMedium3';
    const TABLE_STYLE_MEDIUM4 = 'TableStyleMedium4';
    const TABLE_STYLE_MEDIUM5 = 'TableStyleMedium5';
    const TABLE_STYLE_MEDIUM6 = 'TableStyleMedium6';
    const TABLE_STYLE_MEDIUM7 = 'TableStyleMedium7';
    const TABLE_STYLE_MEDIUM8 = 'TableStyleMedium8';
    const TABLE_STYLE_MEDIUM9 = 'TableStyleMedium9';
    const TABLE_STYLE_MEDIUM10 = 'TableStyleMedium10';
    const TABLE_STYLE_MEDIUM11 = 'TableStyleMedium11';
    const TABLE_STYLE_MEDIUM12 = 'TableStyleMedium12';
    const TABLE_STYLE_MEDIUM13 = 'TableStyleMedium13';
    const TABLE_STYLE_MEDIUM14 = 'TableStyleMedium14';
    const TABLE_STYLE_MEDIUM15 = 'TableStyleMedium15';
    const TABLE_STYLE_MEDIUM16 = 'TableStyleMedium16';
    const TABLE_STYLE_MEDIUM17 = 'TableStyleMedium17';
    const TABLE_STYLE_MEDIUM18 = 'TableStyleMedium18';
    const TABLE_STYLE_MEDIUM19 = 'TableStyleMedium19';
    const TABLE_STYLE_MEDIUM20 = 'TableStyleMedium20';
    const TABLE_STYLE_MEDIUM21 = 'TableStyleMedium21';
    const TABLE_STYLE_MEDIUM22 = 'TableStyleMedium22';
    const TABLE_STYLE_MEDIUM23 = 'TableStyleMedium23';
    const TABLE_STYLE_MEDIUM24 = 'TableStyleMedium24';
    const TABLE_STYLE_MEDIUM25 = 'TableStyleMedium25';
    const TABLE_STYLE_MEDIUM26 = 'TableStyleMedium26';
    const TABLE_STYLE_MEDIUM27 = 'TableStyleMedium27';
    const TABLE_STYLE_MEDIUM28 = 'TableStyleMedium28';
    const TABLE_STYLE_DARK1 = 'TableStyleDark1';
    const TABLE_STYLE_DARK2 = 'TableStyleDark2';
    const TABLE_STYLE_DARK3 = 'TableStyleDark3';
    const TABLE_STYLE_DARK4 = 'TableStyleDark4';
    const TABLE_STYLE_DARK5 = 'TableStyleDark5';
    const TABLE_STYLE_DARK6 = 'TableStyleDark6';
    const TABLE_STYLE_DARK7 = 'TableStyleDark7';
    const TABLE_STYLE_DARK8 = 'TableStyleDark8';
    const TABLE_STYLE_DARK9 = 'TableStyleDark9';
    const TABLE_STYLE_DARK10 = 'TableStyleDark10';
    const TABLE_STYLE_DARK11 = 'TableStyleDark11';

    
    private string $theme;

    
    private bool $showFirstColumn = false;

    
    private bool $showLastColumn = false;

    
    private bool $showRowStripes = false;

    
    private bool $showColumnStripes = false;

    
    private ?TableDxfsStyle $tableStyle = null;

    
    private ?Table $table = null;

    
    public function __construct(string $theme = self::TABLE_STYLE_MEDIUM2)
    {
        $this->theme = $theme;
    }

    
    public function getTheme(): string
    {
        return $this->theme;
    }

    
    public function setTheme(string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    
    public function getShowFirstColumn(): bool
    {
        return $this->showFirstColumn;
    }

    
    public function setShowFirstColumn(bool $showFirstColumn): self
    {
        $this->showFirstColumn = $showFirstColumn;

        return $this;
    }

    
    public function getShowLastColumn(): bool
    {
        return $this->showLastColumn;
    }

    
    public function setShowLastColumn(bool $showLastColumn): self
    {
        $this->showLastColumn = $showLastColumn;

        return $this;
    }

    
    public function getShowRowStripes(): bool
    {
        return $this->showRowStripes;
    }

    
    public function setShowRowStripes(bool $showRowStripes): self
    {
        $this->showRowStripes = $showRowStripes;

        return $this;
    }

    
    public function getShowColumnStripes(): bool
    {
        return $this->showColumnStripes;
    }

    
    public function setShowColumnStripes(bool $showColumnStripes): self
    {
        $this->showColumnStripes = $showColumnStripes;

        return $this;
    }

    
    public function getTableDxfsStyle(): ?TableDxfsStyle
    {
        return $this->tableStyle;
    }

    
    public function setTableDxfsStyle(TableDxfsStyle $tableStyle, array $dxfs): self
    {
        $this->tableStyle = $tableStyle;

        if ($this->tableStyle->getHeaderRow() !== null && isset($dxfs[$this->tableStyle->getHeaderRow()])) {
            $this->tableStyle->setHeaderRowStyle($dxfs[$this->tableStyle->getHeaderRow()]);
        }
        if ($this->tableStyle->getFirstRowStripe() !== null && isset($dxfs[$this->tableStyle->getFirstRowStripe()])) {
            $this->tableStyle->setFirstRowStripeStyle($dxfs[$this->tableStyle->getFirstRowStripe()]);
        }
        if ($this->tableStyle->getSecondRowStripe() !== null && isset($dxfs[$this->tableStyle->getSecondRowStripe()])) {
            $this->tableStyle->setSecondRowStripeStyle($dxfs[$this->tableStyle->getSecondRowStripe()]);
        }

        return $this;
    }

    
    public function getTable(): ?Table
    {
        return $this->table;
    }

    
    public function setTable(?Table $table = null): self
    {
        $this->table = $table;

        return $this;
    }
}
