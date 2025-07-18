<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;


































class BIFFwriter
{
    
    private static ?int $byteOrder = null;

    
    public ?string $_data;

    
    public int $_datasize;

    
    private int $limit = 8224;

    
    public function __construct()
    {
        $this->_data = '';
        $this->_datasize = 0;
    }

    
    public static function getByteOrder(): int
    {
        if (!isset(self::$byteOrder)) {
            
            $teststr = pack('d', 1.2345);
            $number = pack('C8', 0x8D, 0x97, 0x6E, 0x12, 0x83, 0xC0, 0xF3, 0x3F);
            if ($number == $teststr) {
                $byte_order = 0; 
            } elseif ($number == strrev($teststr)) {
                $byte_order = 1; 
            } else {
                
                throw new WriterException('Required floating point format not supported on this platform.');
            }
            self::$byteOrder = $byte_order;
        }

        return self::$byteOrder;
    }

    
    protected function append(string $data): void
    {
        if (strlen($data) - 4 > $this->limit) {
            $data = $this->addContinue($data);
        }
        $this->_data .= $data;
        $this->_datasize += strlen($data);
    }

    
    public function writeData(string $data): string
    {
        if (strlen($data) - 4 > $this->limit) {
            $data = $this->addContinue($data);
        }
        $this->_datasize += strlen($data);

        return $data;
    }

    
    protected function storeBof(int $type): void
    {
        $record = 0x0809; 
        $length = 0x0010;

        
        $unknown = pack('VV', 0x000100D1, 0x00000406);

        $build = 0x0DBB; 
        $year = 0x07CC; 

        $version = 0x0600; 

        $header = pack('vv', $record, $length);
        $data = pack('vvvv', $version, $type, $build, $year);
        $this->append($header . $data . $unknown);
    }

    
    protected function storeEof(): void
    {
        $record = 0x000A; 
        $length = 0x0000; 

        $header = pack('vv', $record, $length);
        $this->append($header);
    }

    
    public function writeEof(): string
    {
        $record = 0x000A; 
        $length = 0x0000; 
        $header = pack('vv', $record, $length);

        return $this->writeData($header);
    }

    
    private function addContinue(string $data): string
    {
        $limit = $this->limit;
        $record = 0x003C; 

        
        
        $tmp = substr($data, 0, 2) . pack('v', $limit) . substr($data, 4, $limit);

        $header = pack('vv', $record, $limit); 

        
        $data_length = strlen($data);
        for ($i = $limit + 4; $i < ($data_length - $limit); $i += $limit) {
            $tmp .= $header;
            $tmp .= substr($data, $i, $limit);
        }

        
        $header = pack('vv', $record, strlen($data) - $i);
        $tmp .= $header;
        $tmp .= substr($data, $i);

        return $tmp;
    }
}
