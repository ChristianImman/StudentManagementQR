<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

class ExceptionHandler
{
    
    public function __construct()
    {
        
        $callable = [Exception::class, 'errorHandlerCallback'];
        set_error_handler($callable, E_ALL);
    }

    
    public function __destruct()
    {
        restore_error_handler();
    }
}
