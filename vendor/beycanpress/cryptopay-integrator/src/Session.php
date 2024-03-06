<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\Integrator;

class Session
{
    /**
     * @return void
     */
    public static function start(): void
    {
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        if (!isset($_SESSION['cp_integrator'])) {
            $_SESSION['cp_integrator'] = [];
        }
        $_SESSION['cp_integrator'][$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION['cp_integrator'][$key] ?? $default;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION['cp_integrator'][$key]);
    }


    /**
     * @param string $key
     * @return void
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION['cp_integrator'][$key]);
    }

    /**
     * @return void
     */
    public static function clear(): void
    {
        self::start();
        session_unset();
    }

    /**
     * @return void
     */
    public static function destroy(): void
    {
        self::start();
        session_destroy();
    }

    /**
     * @return void
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id();
    }
}
