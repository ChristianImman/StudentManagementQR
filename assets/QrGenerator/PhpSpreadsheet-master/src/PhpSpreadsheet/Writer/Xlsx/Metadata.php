<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;

class Metadata extends WriterPart
{
    
    public function writeMetadata(): string
    {
        if (!$this->getParentWriter()->useDynamicArrays()) {
            return '';
        }
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('metadata');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);
        $objWriter->writeAttribute('xmlns:xlrd', Namespaces::DYNAMIC_ARRAY_RICHDATA);
        $objWriter->writeAttribute('xmlns:xda', Namespaces::DYNAMIC_ARRAY);

        $objWriter->startElement('metadataTypes');
        $objWriter->writeAttribute('count', '2');

        $objWriter->startElement('metadataType');
        $objWriter->writeAttribute('name', 'XLDAPR');
        $objWriter->writeAttribute('minSupportedVersion', '120000');
        $objWriter->writeAttribute('copy', '1');
        $objWriter->writeAttribute('pasteAll', '1');
        $objWriter->writeAttribute('pasteValues', '1');
        $objWriter->writeAttribute('merge', '1');
        $objWriter->writeAttribute('splitFirst', '1');
        $objWriter->writeAttribute('rowColShift', '1');
        $objWriter->writeAttribute('clearFormats', '1');
        $objWriter->writeAttribute('clearComments', '1');
        $objWriter->writeAttribute('assign', '1');
        $objWriter->writeAttribute('coerce', '1');
        $objWriter->writeAttribute('cellMeta', '1');
        $objWriter->endElement(); 

        $objWriter->startElement('metadataType');
        $objWriter->writeAttribute('name', 'XLRICHVALUE');
        $objWriter->writeAttribute('minSupportedVersion', '120000');
        $objWriter->writeAttribute('copy', '1');
        $objWriter->writeAttribute('pasteAll', '1');
        $objWriter->writeAttribute('pasteValues', '1');
        $objWriter->writeAttribute('merge', '1');
        $objWriter->writeAttribute('splitFirst', '1');
        $objWriter->writeAttribute('rowColShift', '1');
        $objWriter->writeAttribute('clearFormats', '1');
        $objWriter->writeAttribute('clearComments', '1');
        $objWriter->writeAttribute('assign', '1');
        $objWriter->writeAttribute('coerce', '1');
        $objWriter->endElement(); 

        $objWriter->endElement(); 

        $objWriter->startElement('futureMetadata');
        $objWriter->writeAttribute('name', 'XLDAPR');
        $objWriter->writeAttribute('count', '1');
        $objWriter->startElement('bk');
        $objWriter->startElement('extLst');
        $objWriter->startElement('ext');
        $objWriter->writeAttribute('uri', '{bdbb8cdc-fa1e-496e-a857-3c3f30c029c3}');
        $objWriter->startElement('xda:dynamicArrayProperties');
        $objWriter->writeAttribute('fDynamic', '1');
        $objWriter->writeAttribute('fCollapsed', '0');
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->startElement('futureMetadata');
        $objWriter->writeAttribute('name', 'XLRICHVALUE');
        $objWriter->writeAttribute('count', '1');
        $objWriter->startElement('bk');
        $objWriter->startElement('extLst');
        $objWriter->startElement('ext');
        $objWriter->writeAttribute('uri', '{3e2802c4-a4d2-4d8b-9148-e3be6c30e623}');
        $objWriter->startElement('xlrd:rvb');
        $objWriter->writeAttribute('i', '0');
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->startElement('cellMetadata');
        $objWriter->writeAttribute('count', '1');
        $objWriter->startElement('bk');
        $objWriter->startElement('rc');
        $objWriter->writeAttribute('t', '1');
        $objWriter->writeAttribute('v', '0');
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->startElement('valueMetadata');
        $objWriter->writeAttribute('count', '1');
        $objWriter->startElement('bk');
        $objWriter->startElement('rc');
        $objWriter->writeAttribute('t', '2');
        $objWriter->writeAttribute('v', '0');
        $objWriter->endElement(); 
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        $objWriter->endElement(); 

        
        return $objWriter->getData();
    }
}
