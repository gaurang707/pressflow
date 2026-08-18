<?php

declare(strict_types=1);

namespace PressFlow\Support;

final class Autoloader
{
    private const NAMESPACE_PREFIX = 'PressFlow\\';

    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $className): void
    {
        if (! str_starts_with($className, self::NAMESPACE_PREFIX)) {
            return;
        }

        $relativeClass = substr($className, strlen(self::NAMESPACE_PREFIX));
        $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
