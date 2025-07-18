<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Pdf;

use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;

class Dompdf extends Pdf
{
    
    protected bool $embedImages = true;

    
    protected function createExternalWriterInstance(): \Dompdf\Dompdf
    {
        return new \Dompdf\Dompdf();
    }

    
    public function save($filename, int $flags = 0): void
    {
        $fileHandle = parent::prepareForSave($filename);

        
        $setup = $this->spreadsheet->getSheet($this->getSheetIndex() ?? 0)->getPageSetup();
        $orientation = $this->getOrientation() ?? $setup->getOrientation();
        $orientation = ($orientation === PageSetup::ORIENTATION_LANDSCAPE) ? 'L' : 'P';
        $printPaperSize = $this->getPaperSize() ?? $setup->getPaperSize();
        $paperSize = self::$paperSizes[$printPaperSize] ?? self::$paperSizes[PageSetup::getPaperSizeDefault()] ?? 'LETTER';
        if (is_array($paperSize) && count($paperSize) === 2) {
            $paperSize = [0.0, 0.0, $paperSize[0], $paperSize[1]];
        }

        $orientation = ($orientation == 'L') ? 'landscape' : 'portrait';

        
        $pdf = $this->createExternalWriterInstance();
        $pdf->setPaper($paperSize, $orientation);

        $pdf->loadHtml($this->generateHTMLAll());
        $pdf->render();

        
        fwrite($fileHandle, $pdf->output() ?? '');

        parent::restoreStateAfterSave();
    }
}
