<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\Integrator;

use BeycanPress\CryptoPay\PluginHero\Hook as ProHook;
use BeycanPress\CryptoPayLite\PluginHero\Hook as LiteHook;

class Hook
{
    /**
     * @param string $name
     * @param mixed $callback
     * @param integer $priority
     * @param integer $acceptedArgs
     * @return void
     */
    public static function addAction(string $name, mixed $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (Helpers::exists()) {
            ProHook::addAction($name, $callback, $priority, $acceptedArgs);
        }

        if (Helpers::liteExists()) {
            LiteHook::addAction($name, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return void
     */
    public static function removeAction(string $name, mixed ...$args): void
    {
        if (Helpers::exists()) {
            ProHook::removeAction($name, ...$args);
        }

        if (Helpers::liteExists()) {
            LiteHook::removeAction($name, ...$args);
        }
    }

    /**
     * @param string $name
     * @param mixed $callback
     * @param integer $priority
     * @param integer $acceptedArgs
     * @return void
     */
    public static function addFilter(string $name, mixed $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (Helpers::exists()) {
            ProHook::addFilter($name, $callback, $priority, $acceptedArgs);
        }

        if (Helpers::liteExists()) {
            LiteHook::addFilter($name, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return void
     */
    public static function removeFilter(string $name, mixed ...$args): void
    {
        if (Helpers::exists()) {
            ProHook::removeFilter($name, ...$args);
        }

        if (Helpers::liteExists()) {
            LiteHook::removeFilter($name, ...$args);
        }
    }
}
