<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DocProps extends WriterPart
{
    
    public function writeDocPropsApp(Spreadsheet $spreadsheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Properties');
        $objWriter->writeAttribute('xmlns', Namespaces::EXTENDED_PROPERTIES);
        $objWriter->writeAttribute('xmlns:vt', Namespaces::PROPERTIES_VTYPES);

        
        $objWriter->writeElement('Application', 'Microsoft Excel');

        
        $objWriter->writeElement('DocSecurity', '0');

        
        $objWriter->writeElement('ScaleCrop', 'false');

        
        $objWriter->startElement('HeadingPairs');

        
        $objWriter->startElement('vt:vector');
        $objWriter->writeAttribute('size', '2');
        $objWriter->writeAttribute('baseType', 'variant');

        
        $objWriter->startElement('vt:variant');
        $objWriter->writeElement('vt:lpstr', 'Worksheets');
        $objWriter->endElement();

        
        $objWriter->startElement('vt:variant');
        $objWriter->writeElement('vt:i4', (string) $spreadsheet->getSheetCount());
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('TitlesOfParts');

        
        $objWriter->startElement('vt:vector');
        $objWriter->writeAttribute('size', (string) $spreadsheet->getSheetCount());
        $objWriter->writeAttribute('baseType', 'lpstr');

        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            $objWriter->writeElement('vt:lpstr', $spreadsheet->getSheet($i)->getTitle());
        }

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->writeElement('Company', $spreadsheet->getProperties()->getCompany());

        
        $objWriter->writeElement('Manager', $spreadsheet->getProperties()->getManager());

        
        $objWriter->writeElement('LinksUpToDate', 'false');

        
        $objWriter->writeElement('SharedDoc', 'false');

        
        $objWriter->writeElement('HyperlinkBase', $spreadsheet->getProperties()->getHyperlinkBase());

        
        $objWriter->writeElement('HyperlinksChanged', 'false');

        
        $objWriter->writeElement('AppVersion', '12.0000');

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    public function writeDocPropsCore(Spreadsheet $spreadsheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('cp:coreProperties');
        $objWriter->writeAttribute('xmlns:cp', Namespaces::CORE_PROPERTIES2);
        $objWriter->writeAttribute('xmlns:dc', Namespaces::DC_ELEMENTS);
        $objWriter->writeAttribute('xmlns:dcterms', Namespaces::DC_TERMS);
        $objWriter->writeAttribute('xmlns:dcmitype', Namespaces::DC_DCMITYPE);
        $objWriter->writeAttribute('xmlns:xsi', Namespaces::SCHEMA_INSTANCE);

        
        $objWriter->writeElement('dc:creator', $spreadsheet->getProperties()->getCreator());

        
        $objWriter->writeElement('cp:lastModifiedBy', $spreadsheet->getProperties()->getLastModifiedBy());

        
        $objWriter->startElement('dcterms:created');
        $objWriter->writeAttribute('xsi:type', 'dcterms:W3CDTF');
        $created = $spreadsheet->getProperties()->getCreated();
        $date = Date::dateTimeFromTimestamp("$created");
        $objWriter->writeRawData($date->format(DATE_W3C));
        $objWriter->endElement();

        
        $objWriter->startElement('dcterms:modified');
        $objWriter->writeAttribute('xsi:type', 'dcterms:W3CDTF');
        $created = $spreadsheet->getProperties()->getModified();
        $date = Date::dateTimeFromTimestamp("$created");
        $objWriter->writeRawData($date->format(DATE_W3C));
        $objWriter->endElement();

        
        $objWriter->writeElement('dc:title', $spreadsheet->getProperties()->getTitle());

        
        $objWriter->writeElement('dc:description', $spreadsheet->getProperties()->getDescription());

        
        $objWriter->writeElement('dc:subject', $spreadsheet->getProperties()->getSubject());

        
        $objWriter->writeElement('cp:keywords', $spreadsheet->getProperties()->getKeywords());

        
        $objWriter->writeElement('cp:category', $spreadsheet->getProperties()->getCategory());

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    public function writeDocPropsCustom(Spreadsheet $spreadsheet): ?string
    {
        $customPropertyList = $spreadsheet->getProperties()->getCustomProperties();
        if (empty($customPropertyList)) {
            return null;
        }

        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Properties');
        $objWriter->writeAttribute('xmlns', Namespaces::CUSTOM_PROPERTIES);
        $objWriter->writeAttribute('xmlns:vt', Namespaces::PROPERTIES_VTYPES);

        foreach ($customPropertyList as $key => $customProperty) {
            $propertyValue = $spreadsheet->getProperties()->getCustomPropertyValue($customProperty);
            $propertyType = $spreadsheet->getProperties()->getCustomPropertyType($customProperty);

            $objWriter->startElement('property');
            $objWriter->writeAttribute('fmtid', '{D5CDD505-2E9C-101B-9397-08002B2CF9AE}');
            $objWriter->writeAttribute('pid', (string) ($key + 2));
            $objWriter->writeAttribute('name', $customProperty);

            switch ($propertyType) {
                case Properties::PROPERTY_TYPE_INTEGER:
                    $objWriter->writeElement('vt:i4', (string) $propertyValue);

                    break;
                case Properties::PROPERTY_TYPE_FLOAT:
                    $objWriter->writeElement('vt:r8', sprintf('%F', $propertyValue));

                    break;
                case Properties::PROPERTY_TYPE_BOOLEAN:
                    $objWriter->writeElement('vt:bool', ($propertyValue) ? 'true' : 'false');

                    break;
                case Properties::PROPERTY_TYPE_DATE:
                    $objWriter->startElement('vt:filetime');
                    $date = Date::dateTimeFromTimestamp("$propertyValue");
                    $objWriter->writeRawData($date->format(DATE_W3C));
                    $objWriter->endElement();

                    break;
                default:
                    $objWriter->writeElement('vt:lpwstr', (string) $propertyValue);

                    break;
            }

            $objWriter->endElement();
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }
}
