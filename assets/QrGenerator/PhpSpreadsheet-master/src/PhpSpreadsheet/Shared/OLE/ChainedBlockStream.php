<?php

namespace PhpOffice\PhpSpreadsheet\Shared\OLE;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Shared\OLE;

class ChainedBlockStream
{
    
    public $context;

    
    public ?OLE $ole = null;

    
    public array $params = [];

    
    public string $data;

    
    public int $pos = 0;

    
    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool 
    {
        if ($mode[0] !== 'r') {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Only reading is supported', E_USER_WARNING);
            }

            return false;
        }

        
        parse_str(substr($path, 25), $this->params);
        if (!isset($this->params['oleInstanceId'], $this->params['blockId'], $GLOBALS['_OLE_INSTANCES'][$this->params['oleInstanceId']])) { /
    public function stream_close(): void 
    {
        $this->ole = null;
        unset($GLOBALS['_OLE_INSTANCES']);
    }

    
    public function stream_read(int $count): bool|string 
    {
        if ($this->stream_eof()) {
            return false;
        }
        $s = substr($this->data, (int) $this->pos, $count);
        $this->pos += $count;

        return $s;
    }

    
    public function stream_eof(): bool 
    {
        return $this->pos >= strlen($this->data);
    }

    
    public function stream_tell(): int 
    {
        return $this->pos;
    }

    
    public function stream_seek(int $offset, int $whence): bool 
    {
        if ($whence == SEEK_SET && $offset >= 0) {
            $this->pos = $offset;
        } elseif ($whence == SEEK_CUR && -$offset <= $this->pos) {
            $this->pos += $offset;
        } elseif ($whence == SEEK_END && -$offset <= count($this->data)) { 
            $this->pos = strlen($this->data) + $offset;
        } else {
            return false;
        }

        return true;
    }

    
    public function stream_stat(): array 
    {
        return [
            'size' => strlen($this->data),
        ];
    }

    
    
    
    
    
    
    
    
    
    
    
}
