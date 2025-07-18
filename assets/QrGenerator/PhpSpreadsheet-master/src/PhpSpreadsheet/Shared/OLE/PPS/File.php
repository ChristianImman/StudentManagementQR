<?php

namespace PhpOffice\PhpSpreadsheet\Shared\OLE\PPS;



















use PhpOffice\PhpSpreadsheet\Shared\OLE;
use PhpOffice\PhpSpreadsheet\Shared\OLE\PPS;


class File extends PPS
{
    
    public function __construct(string $name)
    {
        parent::__construct(null, $name, OLE::OLE_PPS_TYPE_FILE, null, null, null, null, null, '', []);
    }

    
    public function init(): bool
    {
        return true;
    }

    
    public function append(string $data): void
    {
        $this->_data .= $data;
    }
}
