<?php

namespace Padosoft\QueryMonitor;

class QueryCounter
{
    protected static int $queryCount = 0;
    protected static array $contextInfo = [];

    /**
     * Resetta il contatore query e memorizza il contesto (es: request info, command info)
     */
    public static function reset(array $contextInfo = []): void
    {
        self::$queryCount = 0;
        self::$contextInfo = $contextInfo;
    }

    /**
     * Incrementa il contatore delle query
     */
    public static function increment(): void
    {
        self::$queryCount++;
    }

    /**
     * Restituisce il numero totale di query eseguite
     */
    public static function getCount(): int
    {
        return self::$queryCount;
    }

    /**
     * Restituisce le info di contesto salvate (url, metodo, command, ecc.)
     */
    public static function getContextInfo(): array
    {
        return self::$contextInfo;
    }
}
