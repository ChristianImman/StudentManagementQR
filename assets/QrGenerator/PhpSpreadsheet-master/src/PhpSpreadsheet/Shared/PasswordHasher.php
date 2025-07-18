<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use PhpOffice\PhpSpreadsheet\Exception as SpException;
use PhpOffice\PhpSpreadsheet\Worksheet\Protection;

class PasswordHasher
{
    const MAX_PASSWORD_LENGTH = 255;

    
    private static function getAlgorithm(string $algorithmName): string
    {
        if (!$algorithmName) {
            return '';
        }

        
        $mapping = [
            Protection::ALGORITHM_MD2 => 'md2',
            Protection::ALGORITHM_MD4 => 'md4',
            Protection::ALGORITHM_MD5 => 'md5',
            Protection::ALGORITHM_SHA_1 => 'sha1',
            Protection::ALGORITHM_SHA_256 => 'sha256',
            Protection::ALGORITHM_SHA_384 => 'sha384',
            Protection::ALGORITHM_SHA_512 => 'sha512',
            Protection::ALGORITHM_RIPEMD_128 => 'ripemd128',
            Protection::ALGORITHM_RIPEMD_160 => 'ripemd160',
            Protection::ALGORITHM_WHIRLPOOL => 'whirlpool',
        ];

        if (array_key_exists($algorithmName, $mapping)) {
            return $mapping[$algorithmName];
        }

        throw new SpException('Unsupported password algorithm: ' . $algorithmName);
    }

    
    private static function defaultHashPassword(string $password): string
    {
        $verifier = 0;
        $pwlen = strlen($password);
        $passwordArray = pack('c', $pwlen) . $password;
        for ($i = $pwlen; $i >= 0; --$i) {
            $intermediate1 = (($verifier & 0x4000) === 0) ? 0 : 1;
            $intermediate2 = 2 * $verifier;
            $intermediate2 = $intermediate2 & 0x7FFF;
            $intermediate3 = $intermediate1 | $intermediate2;
            $verifier = $intermediate3 ^ ord($passwordArray[$i]);
        }
        $verifier ^= 0xCE4B;

        return strtoupper(dechex($verifier));
    }

    
    public static function hashPassword(string $password, string $algorithm = '', string $salt = '', int $spinCount = 10000): string
    {
        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            throw new SpException('Password exceeds ' . self::MAX_PASSWORD_LENGTH . ' characters');
        }
        $phpAlgorithm = self::getAlgorithm($algorithm);
        if (!$phpAlgorithm) {
            return self::defaultHashPassword($password);
        }

        $saltValue = base64_decode($salt);
        $encodedPassword = mb_convert_encoding($password, 'UCS-2LE', 'UTF-8');

        $hashValue = hash($phpAlgorithm, $saltValue . $encodedPassword, true);
        for ($i = 0; $i < $spinCount; ++$i) {
            $hashValue = hash($phpAlgorithm, $hashValue . pack('L', $i), true);
        }

        return base64_encode($hashValue);
    }
}
