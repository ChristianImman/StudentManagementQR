<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

class HeaderFooterDrawing extends Drawing
{
    
    public function getHashCode(): string
    {
        return md5(
            $this->getPath()
            . $this->name
            . $this->offsetX
            . $this->offsetY
            . $this->width
            . $this->height
            . __CLASS__
        );
    }
}
