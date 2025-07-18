<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Web;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Settings;
use Psr\Http\Client\ClientExceptionInterface;

class Service
{
    
    public static function webService(string $url): string
    {
        $url = trim($url);
        if (strlen($url) > 2048) {
            return ExcelError::VALUE(); 
        }

        if (!preg_match('/^http[s]?:\/\
            return ExcelError::VALUE(); 
        }

        
        $client = Settings::getHttpClient();
        $requestFactory = Settings::getRequestFactory();
        $request = $requestFactory->createRequest('GET', $url);

        try {
            $response = $client->sendRequest($request);
        } catch (ClientExceptionInterface) {
            return ExcelError::VALUE(); 
        }

        if ($response->getStatusCode() != 200) {
            return ExcelError::VALUE(); 
        }

        $output = $response->getBody()->getContents();
        if (strlen($output) > 32767) {
            return ExcelError::VALUE(); 
        }

        return $output;
    }

    
    public static function urlEncode(mixed $text): string
    {
        if (!is_string($text)) {
            return ExcelError::VALUE();
        }

        return str_replace('+', '%20', urlencode($text));
    }
}
