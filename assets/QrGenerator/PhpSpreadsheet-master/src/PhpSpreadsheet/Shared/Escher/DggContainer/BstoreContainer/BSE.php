<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer;

use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer;

class BSE
{
    const BLIPTYPE_ERROR = 0x00;
    const BLIPTYPE_UNKNOWN = 0x01;
    const BLIPTYPE_EMF = 0x02;
    const BLIPTYPE_WMF = 0x03;
    const BLIPTYPE_PICT = 0x04;
    const BLIPTYPE_JPEG = 0x05;
    const BLIPTYPE_PNG = 0x06;
    const BLIPTYPE_DIB = 0x07;
    const BLIPTYPE_TIFF = 0x11;
    const BLIPTYPE_CMYKJPEG = 0x12;

    
    private BstoreContainer $parent;

    
    private ?BSE\Blip $blip = null;

    
    private int $blipType;

    
    public function setParent(BstoreContainer $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): BstoreContainer
    {
        return $this->parent;
    }

    
    public function getBlip(): ?BSE\Blip
    {
        return $this->blip;
    }

    
    public function setBlip(BSE\Blip $blip): void
    {
        $this->blip = $blip;
        $blip->setParent($this);
    }

    
    public function getBlipType(): int
    {
        return $this->blipType;
    }

    
    public function setBlipType(int $blipType): void
    {
        $this->blipType = $blipType;
    }
}
