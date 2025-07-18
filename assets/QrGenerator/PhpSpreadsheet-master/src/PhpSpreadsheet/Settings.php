<?php

namespace PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Chart\Renderer\IRenderer;
use PhpOffice\PhpSpreadsheet\Collection\Memory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;

class Settings
{
    
    private static ?string $chartRenderer = null;

    
    private static ?CacheInterface $cache = null;

    
    private static ?ClientInterface $httpClient = null;

    private static ?RequestFactoryInterface $requestFactory = null;

    
    public static function setLocale(string $locale): bool
    {
        return Calculation::getInstance()->setLocale($locale);
    }

    public static function getLocale(): string
    {
        return Calculation::getInstance()->getLocale();
    }

    
    public static function setChartRenderer(string $rendererClassName): void
    {
        if (!is_a($rendererClassName, IRenderer::class, true)) {
            throw new Exception('Chart renderer must implement ' . IRenderer::class);
        }

        self::$chartRenderer = $rendererClassName;
    }

    public static function unsetChartRenderer(): void
    {
        self::$chartRenderer = null;
    }

    
    public static function getChartRenderer(): ?string
    {
        return self::$chartRenderer;
    }

    public static function htmlEntityFlags(): int
    {
        return ENT_COMPAT;
    }

    
    public static function setCache(?CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    
    public static function getCache(): CacheInterface
    {
        if (!self::$cache) {
            self::$cache = self::useSimpleCacheVersion3() ? new Memory\SimpleCache3() : new Memory\SimpleCache1();
        }

        return self::$cache;
    }

    public static function useSimpleCacheVersion3(): bool
    {
        return (new ReflectionClass(CacheInterface::class))->getMethod('get')->getReturnType() !== null;
    }

    
    public static function setHttpClient(ClientInterface $httpClient, RequestFactoryInterface $requestFactory): void
    {
        self::$httpClient = $httpClient;
        self::$requestFactory = $requestFactory;
    }

    
    public static function unsetHttpClient(): void
    {
        self::$httpClient = null;
        self::$requestFactory = null;
    }

    
    public static function getHttpClient(): ClientInterface
    {
        if (!self::$httpClient || !self::$requestFactory) {
            throw new Exception('HTTP client must be configured via Settings::setHttpClient() to be able to use WEBSERVICE function.');
        }

        return self::$httpClient;
    }

    
    public static function getRequestFactory(): RequestFactoryInterface
    {
        if (!self::$httpClient || !self::$requestFactory) {
            throw new Exception('HTTP client must be configured via Settings::setHttpClient() to be able to use WEBSERVICE function.');
        }

        return self::$requestFactory;
    }
}
