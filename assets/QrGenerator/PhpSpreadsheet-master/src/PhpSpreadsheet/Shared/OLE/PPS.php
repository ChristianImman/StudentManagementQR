<?php

namespace PhpOffice\PhpSpreadsheet\Shared\OLE;



















use PhpOffice\PhpSpreadsheet\Shared\OLE;


class PPS
{
    private const ALL_ONE_BITS = (PHP_INT_SIZE > 4) ? 0xFFFFFFFF : -1;

    
    public int $No;

    
    public string $Name;

    
    public int $Type;

    
    public int $PrevPps;

    
    public int $NextPps;

    
    public int $DirPps;

    
    public float|int $Time1st;

    
    public float|int $Time2nd;

    
    public ?int $startBlock = null;

    
    public int $Size;

    
    public string $_data = '';

    
    public array $children = [];

    
    public OLE $ole;

    
    public function __construct(?int $No, ?string $name, ?int $type, ?int $prev, ?int $next, ?int $dir, $time_1st, $time_2nd, ?string $data, array $children)
    {
        $this->No = (int) $No;
        $this->Name = (string) $name;
        $this->Type = (int) $type;
        $this->PrevPps = (int) $prev;
        $this->NextPps = (int) $next;
        $this->DirPps = (int) $dir;
        $this->Time1st = $time_1st ?? 0;
        $this->Time2nd = $time_2nd ?? 0;
        $this->_data = (string) $data;
        $this->children = $children;
        $this->Size = strlen((string) $data);
    }

    
    public function getDataLen(): int
    {
        
        
        

        return strlen($this->_data);
    }

    
    public function getPpsWk(): string
    {
        $ret = str_pad($this->Name, 64, "\x00");

        $ret .= pack('v', strlen($this->Name) + 2)  
            . pack('c', $this->Type)              
            . pack('c', 0x00) 
            . pack('V', $this->PrevPps) 
            . pack('V', $this->NextPps) 
            . pack('V', $this->DirPps)  
            . "\x00\x09\x02\x00"                  
            . "\x00\x00\x00\x00"                  
            . "\xc0\x00\x00\x00"                  
            . "\x00\x00\x00\x46"                  
            . "\x00\x00\x00\x00"                  
            . OLE::localDateToOLE($this->Time1st)          
            . OLE::localDateToOLE($this->Time2nd)          
            . pack('V', $this->startBlock ?? 0)  
            . pack('V', $this->Size)               
            . pack('V', 0); 

        return $ret;
    }

    
    public static function savePpsSetPnt(array &$raList, mixed $to_save, int $depth = 0): int
    {
        if (!is_array($to_save) || (empty($to_save))) {
            return self::ALL_ONE_BITS;
        }
        
        if (count($to_save) == 1) {
            $cnt = count($raList);
            
            $raList[$cnt] = ($depth == 0) ? $to_save[0] : clone $to_save[0];
            $raList[$cnt]->No = $cnt;
            $raList[$cnt]->PrevPps = self::ALL_ONE_BITS;
            $raList[$cnt]->NextPps = self::ALL_ONE_BITS;
            $raList[$cnt]->DirPps = self::savePpsSetPnt($raList, @$raList[$cnt]->children, $depth++);
        } else {
            $iPos = (int) floor(count($to_save) / 2);
            $aPrev = array_slice($to_save, 0, $iPos);
            $aNext = array_slice($to_save, $iPos + 1);
            $cnt = count($raList);
            
            $raList[$cnt] = ($depth == 0) ? $to_save[$iPos] : clone $to_save[$iPos];
            $raList[$cnt]->No = $cnt;
            $raList[$cnt]->PrevPps = self::savePpsSetPnt($raList, $aPrev, $depth++);
            $raList[$cnt]->NextPps = self::savePpsSetPnt($raList, $aNext, $depth++);
            $raList[$cnt]->DirPps = self::savePpsSetPnt($raList, @$raList[$cnt]->children, $depth++);
        }

        return $cnt;
    }
}
