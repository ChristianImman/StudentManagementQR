<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Comment;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Comments extends WriterPart
{
    private const VALID_HORIZONTAL_ALIGNMENT = [
        Alignment::HORIZONTAL_CENTER,
        Alignment::HORIZONTAL_DISTRIBUTED,
        Alignment::HORIZONTAL_JUSTIFY,
        Alignment::HORIZONTAL_LEFT,
        Alignment::HORIZONTAL_RIGHT,
    ];

    
    public function writeComments(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $comments = $worksheet->getComments();

        
        $authors = [];
        $authorId = 0;
        foreach ($comments as $comment) {
            if (!isset($authors[$comment->getAuthor()])) {
                $authors[$comment->getAuthor()] = $authorId++;
            }
        }

        
        $objWriter->startElement('comments');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);

        
        $objWriter->startElement('authors');
        foreach ($authors as $author => $index) {
            $objWriter->writeElement('author', $author);
        }
        $objWriter->endElement();

        
        $objWriter->startElement('commentList');
        foreach ($comments as $key => $value) {
            $this->writeComment($objWriter, $key, $value, $authors);
        }
        $objWriter->endElement();

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    private function writeComment(XMLWriter $objWriter, string $cellReference, Comment $comment, array $authors): void
    {
        
        $objWriter->startElement('comment');
        $objWriter->writeAttribute('ref', $cellReference);
        $objWriter->writeAttribute('authorId', (string) $authors[$comment->getAuthor()]);

        
        $objWriter->startElement('text');
        $this->getParentWriter()->getWriterPartstringtable()->writeRichText($objWriter, $comment->getText());
        $objWriter->endElement();

        $objWriter->endElement();
    }

    
    public function writeVMLComments(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $comments = $worksheet->getComments();

        
        $objWriter->startElement('xml');
        $objWriter->writeAttribute('xmlns:v', Namespaces::URN_VML);
        $objWriter->writeAttribute('xmlns:o', Namespaces::URN_MSOFFICE);
        $objWriter->writeAttribute('xmlns:x', Namespaces::URN_EXCEL);

        
        $objWriter->startElement('o:shapelayout');
        $objWriter->writeAttribute('v:ext', 'edit');

        
        $objWriter->startElement('o:idmap');
        $objWriter->writeAttribute('v:ext', 'edit');
        $objWriter->writeAttribute('data', '1');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('v:shapetype');
        $objWriter->writeAttribute('id', '_x0000_t202');
        $objWriter->writeAttribute('coordsize', '21600,21600');
        $objWriter->writeAttribute('o:spt', '202');
        $objWriter->writeAttribute('path', 'm,l,21600r21600,l21600,xe');

        
        $objWriter->startElement('v:stroke');
        $objWriter->writeAttribute('joinstyle', 'miter');
        $objWriter->endElement();

        
        $objWriter->startElement('v:path');
        $objWriter->writeAttribute('gradientshapeok', 't');
        $objWriter->writeAttribute('o:connecttype', 'rect');
        $objWriter->endElement();

        $objWriter->endElement();

        
        foreach ($comments as $key => $value) {
            $this->writeVMLComment($objWriter, $key, $value);
        }

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    private function writeVMLComment(XMLWriter $objWriter, string $cellReference, Comment $comment): void
    {
        
        [$column, $row] = Coordinate::indexesFromString($cellReference);
        $id = 1024 + $column + $row;
        $id = substr("$id", 0, 4);

        
        $objWriter->startElement('v:shape');
        $objWriter->writeAttribute('id', '_x0000_s' . $id);
        $objWriter->writeAttribute('type', '
        $objWriter->writeAttribute('style', 'position:absolute;margin-left:' . $comment->getMarginLeft() . ';margin-top:' . $comment->getMarginTop() . ';width:' . $comment->getWidth() . ';height:' . $comment->getHeight() . ';z-index:1;visibility:' . ($comment->getVisible() ? 'visible' : 'hidden'));
        $objWriter->writeAttribute('fillcolor', '
        $objWriter->writeAttribute('o:insetmode', 'auto');

        
        $objWriter->startElement('v:fill');
        $objWriter->writeAttribute('color2', '
        if ($comment->hasBackgroundImage()) {
            $bgImage = $comment->getBackgroundImage();
            $objWriter->writeAttribute('o:relid', 'rId' . $bgImage->getImageIndex());
            $objWriter->writeAttribute('o:title', $bgImage->getName());
            $objWriter->writeAttribute('type', 'frame');
        }
        $objWriter->endElement();

        
        $objWriter->startElement('v:shadow');
        $objWriter->writeAttribute('on', 't');
        $objWriter->writeAttribute('color', 'black');
        $objWriter->writeAttribute('obscured', 't');
        $objWriter->endElement();

        
        $objWriter->startElement('v:path');
        $objWriter->writeAttribute('o:connecttype', 'none');
        $objWriter->endElement();

        
        $textBoxArray = [Comment::TEXTBOX_DIRECTION_RTL => 'rtl', Comment::TEXTBOX_DIRECTION_LTR => 'ltr'];
        $textboxRtl = $textBoxArray[strtolower($comment->getTextBoxDirection())] ?? 'auto';
        $objWriter->startElement('v:textbox');
        $objWriter->writeAttribute('style', "mso-direction-alt:$textboxRtl");

        
        $objWriter->startElement('div');
        $objWriter->writeAttribute('style', ($textboxRtl === 'rtl' ? 'text-align:right;direction:rtl' : 'text-align:left'));
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('x:ClientData');
        $objWriter->writeAttribute('ObjectType', 'Note');

        
        $objWriter->writeElement('x:MoveWithCells', '');

        
        $objWriter->writeElement('x:SizeWithCells', '');

        
        $objWriter->writeElement('x:AutoFill', 'False');

        
        $alignment = strtolower($comment->getAlignment());
        if (in_array($alignment, self::VALID_HORIZONTAL_ALIGNMENT, true)) {
            $objWriter->writeElement('x:TextHAlign', ucfirst($alignment));
        }

        
        $objWriter->writeElement('x:Row', (string) ($row - 1));

        
        $objWriter->writeElement('x:Column', (string) ($column - 1));

        $objWriter->endElement();

        $objWriter->endElement();
    }
}
