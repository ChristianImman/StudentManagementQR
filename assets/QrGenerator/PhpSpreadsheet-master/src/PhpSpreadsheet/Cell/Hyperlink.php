<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

class Hyperlink
{
    
    private string $url;

    
    private string $tooltip;

    
    public function __construct(string $url = '', string $tooltip = '')
    {
        
        $this->url = $url;
        $this->tooltip = $tooltip;
    }

    
    public function getUrl(): string
    {
        return $this->url;
    }

    
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    
    public function getTooltip(): string
    {
        return $this->tooltip;
    }

    
    public function setTooltip(string $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    
    public function isInternal(): bool
    {
        return str_contains($this->url, 'sheet://');
    }

    public function getTypeHyperlink(): string
    {
        return $this->isInternal() ? '' : 'External';
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->url
            . $this->tooltip
            . __CLASS__
        );
    }
}
