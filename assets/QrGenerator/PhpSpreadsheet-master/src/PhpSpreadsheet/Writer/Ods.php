<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Content;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Meta;
use PhpOffice\PhpSpreadsheet\Writer\Ods\MetaInf;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Mimetype;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Settings;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Styles;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Thumbnails;
use ZipStream\Exception\OverflowException;
use ZipStream\ZipStream;

class Ods extends BaseWriter
{
    
    private Spreadsheet $spreadSheet;

    private Content $writerPartContent;

    private Meta $writerPartMeta;

    private MetaInf $writerPartMetaInf;

    private Mimetype $writerPartMimetype;

    private Settings $writerPartSettings;

    private Styles $writerPartStyles;

    private Thumbnails $writerPartThumbnails;

    
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->setSpreadsheet($spreadsheet);

        $this->writerPartContent = new Content($this);
        $this->writerPartMeta = new Meta($this);
        $this->writerPartMetaInf = new MetaInf($this);
        $this->writerPartMimetype = new Mimetype($this);
        $this->writerPartSettings = new Settings($this);
        $this->writerPartStyles = new Styles($this);
        $this->writerPartThumbnails = new Thumbnails($this);
    }

    public function getWriterPartContent(): Content
    {
        return $this->writerPartContent;
    }

    public function getWriterPartMeta(): Meta
    {
        return $this->writerPartMeta;
    }

    public function getWriterPartMetaInf(): MetaInf
    {
        return $this->writerPartMetaInf;
    }

    public function getWriterPartMimetype(): Mimetype
    {
        return $this->writerPartMimetype;
    }

    public function getWriterPartSettings(): Settings
    {
        return $this->writerPartSettings;
    }

    public function getWriterPartStyles(): Styles
    {
        return $this->writerPartStyles;
    }

    public function getWriterPartThumbnails(): Thumbnails
    {
        return $this->writerPartThumbnails;
    }

    
    public function save($filename, int $flags = 0): void
    {
        $this->processFlags($flags);

        
        $this->spreadSheet->garbageCollect();

        $this->openFileHandle($filename);

        $zip = $this->createZip();

        $zip->addFile('META-INF/manifest.xml', $this->getWriterPartMetaInf()->write());
        $zip->addFile('Thumbnails/thumbnail.png', $this->getWriterPartthumbnails()->write());
        
        $zip->addFile('settings.xml', $this->getWriterPartsettings()->write());
        $zip->addFile('content.xml', $this->getWriterPartcontent()->write());
        $zip->addFile('meta.xml', $this->getWriterPartmeta()->write());
        $zip->addFile('mimetype', $this->getWriterPartmimetype()->write());
        $zip->addFile('styles.xml', $this->getWriterPartstyles()->write());

        
        try {
            $zip->finish();
        } catch (OverflowException) {
            throw new WriterException('Could not close resource.');
        }

        $this->maybeCloseFileHandle();
    }

    
    private function createZip(): ZipStream
    {
        
        if (!is_resource($this->fileHandle)) {
            throw new WriterException('Could not open resource for writing.');
        }

        
        return ZipStream0::newZipStream($this->fileHandle);
    }

    
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadSheet;
    }

    
    public function setSpreadsheet(Spreadsheet $spreadsheet): static
    {
        $this->spreadSheet = $spreadsheet;

        return $this;
    }
}
