<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer\SpContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE\Blip;

class Escher
{
    const DGGCONTAINER = 0xF000;
    const BSTORECONTAINER = 0xF001;
    const DGCONTAINER = 0xF002;
    const SPGRCONTAINER = 0xF003;
    const SPCONTAINER = 0xF004;
    const DGG = 0xF006;
    const BSE = 0xF007;
    const DG = 0xF008;
    const SPGR = 0xF009;
    const SP = 0xF00A;
    const OPT = 0xF00B;
    const CLIENTTEXTBOX = 0xF00D;
    const CLIENTANCHOR = 0xF010;
    const CLIENTDATA = 0xF011;
    const BLIPJPEG = 0xF01D;
    const BLIPPNG = 0xF01E;
    const SPLITMENUCOLORS = 0xF11E;
    const TERTIARYOPT = 0xF122;

    
    private string $data;

    
    private int $dataSize;

    
    private int $pos;

    
    private BSE|BstoreContainer|DgContainer|DggContainer|\PhpOffice\PhpSpreadsheet\Shared\Escher|SpContainer|SpgrContainer $object;

    
    public function __construct(BSE|BstoreContainer|DgContainer|DggContainer|\PhpOffice\PhpSpreadsheet\Shared\Escher|SpContainer|SpgrContainer $object)
    {
        $this->object = $object;
    }

    private const WHICH_ROUTINE = [
        self::DGGCONTAINER => 'readDggContainer',
        self::DGG => 'readDgg',
        self::BSTORECONTAINER => 'readBstoreContainer',
        self::BSE => 'readBSE',
        self::BLIPJPEG => 'readBlipJPEG',
        self::BLIPPNG => 'readBlipPNG',
        self::OPT => 'readOPT',
        self::TERTIARYOPT => 'readTertiaryOPT',
        self::SPLITMENUCOLORS => 'readSplitMenuColors',
        self::DGCONTAINER => 'readDgContainer',
        self::DG => 'readDg',
        self::SPGRCONTAINER => 'readSpgrContainer',
        self::SPCONTAINER => 'readSpContainer',
        self::SPGR => 'readSpgr',
        self::SP => 'readSp',
        self::CLIENTTEXTBOX => 'readClientTextbox',
        self::CLIENTANCHOR => 'readClientAnchor',
        self::CLIENTDATA => 'readClientData',
    ];

    
    public function load(string $data): BSE|BstoreContainer|DgContainer|DggContainer|\PhpOffice\PhpSpreadsheet\Shared\Escher|SpContainer|SpgrContainer
    {
        $this->data = $data;

        
        $this->dataSize = strlen($this->data);

        $this->pos = 0;

        
        while ($this->pos < $this->dataSize) {
            
            $fbt = Xls::getUInt2d($this->data, $this->pos + 2);
            $routine = self::WHICH_ROUTINE[$fbt] ?? 'readDefault';
            if (method_exists($this, $routine)) {
                $this->$routine();
            }
        }

        return $this->object;
    }

    
    private function readDefault(): void
    {
        
        

        
        

        
        

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readDggContainer(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        
        $dggContainer = new DggContainer();
        $this->applyAttribute('setDggContainer', $dggContainer);
        $reader = new self($dggContainer);
        $reader->load($recordData);
    }

    
    private function readDgg(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readBstoreContainer(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        
        $bstoreContainer = new BstoreContainer();
        $this->applyAttribute('setBstoreContainer', $bstoreContainer);
        $reader = new self($bstoreContainer);
        $reader->load($recordData);
    }

    
    private function readBSE(): void
    {
        

        
        $recInstance = (0xFFF0 & Xls::getUInt2d($this->data, $this->pos)) >> 4;

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        
        $BSE = new BSE();
        $this->applyAttribute('addBSE', $BSE);

        $BSE->setBLIPType($recInstance);

        
        

        
        

        
        

        
        

        
        

        
        

        
        

        
        

        
        $cbName = ord($recordData[33]);

        
        

        
        

        
        

        
        $blipData = substr($recordData, 36 + $cbName);

        
        $reader = new self($BSE);
        $reader->load($blipData);
    }

    
    private function readBlipJPEG(): void
    {
        

        
        $recInstance = (0xFFF0 & Xls::getUInt2d($this->data, $this->pos)) >> 4;

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        $pos = 0;

        
        
        $pos += 16;

        
        if (in_array($recInstance, [0x046B, 0x06E3])) {
            
            $pos += 16;
        }

        
        
        ++$pos;

        
        $data = substr($recordData, $pos);

        $blip = new Blip();
        $blip->setData($data);

        $this->applyAttribute('setBlip', $blip);
    }

    
    private function readBlipPNG(): void
    {
        

        
        $recInstance = (0xFFF0 & Xls::getUInt2d($this->data, $this->pos)) >> 4;

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        $pos = 0;

        
        
        $pos += 16;

        
        if ($recInstance == 0x06E1) {
            
            $pos += 16;
        }

        
        
        ++$pos;

        
        $data = substr($recordData, $pos);

        $blip = new Blip();
        $blip->setData($data);

        $this->applyAttribute('setBlip', $blip);
    }

    
    private function readOPT(): void
    {
        

        
        $recInstance = (0xFFF0 & Xls::getUInt2d($this->data, $this->pos)) >> 4;

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        $this->readOfficeArtRGFOPTE($recordData, $recInstance);
    }

    
    private function readTertiaryOPT(): void
    {
        

        
        

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readSplitMenuColors(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readDgContainer(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        
        $dgContainer = new DgContainer();
        $this->applyAttribute('setDgContainer', $dgContainer);
        $reader = new self($dgContainer);
        $reader->load($recordData);
    }

    
    private function readDg(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readSpgrContainer(): void
    {
        

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        
        $spgrContainer = new SpgrContainer();

        if ($this->object instanceof DgContainer) {
            
            $this->object->setSpgrContainer($spgrContainer);
        } elseif ($this->object instanceof SpgrContainer) {
            
            $this->object->addChild($spgrContainer);
        }

        $reader = new self($spgrContainer);
        $reader->load($recordData);
    }

    
    private function readSpContainer(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $spContainer = new SpContainer();
        $this->applyAttribute('addChild', $spContainer);

        
        $this->pos += 8 + $length;

        
        $reader = new self($spContainer);
        $reader->load($recordData);
    }

    
    private function readSpgr(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readSp(): void
    {
        

        
        

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readClientTextbox(): void
    {
        

        
        

        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readClientAnchor(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        $recordData = substr($this->data, $this->pos + 8, $length);

        
        $this->pos += 8 + $length;

        
        $c1 = Xls::getUInt2d($recordData, 2);

        
        $startOffsetX = Xls::getUInt2d($recordData, 4);

        
        $r1 = Xls::getUInt2d($recordData, 6);

        
        $startOffsetY = Xls::getUInt2d($recordData, 8);

        
        $c2 = Xls::getUInt2d($recordData, 10);

        
        $endOffsetX = Xls::getUInt2d($recordData, 12);

        
        $r2 = Xls::getUInt2d($recordData, 14);

        
        $endOffsetY = Xls::getUInt2d($recordData, 16);

        $this->applyAttribute('setStartCoordinates', Coordinate::stringFromColumnIndex($c1 + 1) . ($r1 + 1));
        $this->applyAttribute('setStartOffsetX', $startOffsetX);
        $this->applyAttribute('setStartOffsetY', $startOffsetY);
        $this->applyAttribute('setEndCoordinates', Coordinate::stringFromColumnIndex($c2 + 1) . ($r2 + 1));
        $this->applyAttribute('setEndOffsetX', $endOffsetX);
        $this->applyAttribute('setEndOffsetY', $endOffsetY);
    }

    private function applyAttribute(string $name, mixed $value): void
    {
        if (method_exists($this->object, $name)) {
            $this->object->$name($value);
        }
    }

    
    private function readClientData(): void
    {
        $length = Xls::getInt4d($this->data, $this->pos + 4);
        

        
        $this->pos += 8 + $length;
    }

    
    private function readOfficeArtRGFOPTE(string $data, int $n): void
    {
        $splicedComplexData = substr($data, 6 * $n);

        
        for ($i = 0; $i < $n; ++$i) {
            
            $fopte = substr($data, 6 * $i, 6);

            
            $opid = Xls::getUInt2d($fopte, 0);

            
            $opidOpid = (0x3FFF & $opid) >> 0;

            
            

            
            $opidFComplex = (0x8000 & $opid) >> 15;

            
            $op = Xls::getInt4d($fopte, 2);

            if ($opidFComplex) {
                $complexData = substr($splicedComplexData, 0, $op);
                $splicedComplexData = substr($splicedComplexData, $op);

                
                $value = $complexData;
            } else {
                
                $value = $op;
            }

            if (method_exists($this->object, 'setOPT')) {
                $this->object->setOPT($opidOpid, $value);
            }
        }
    }
}
