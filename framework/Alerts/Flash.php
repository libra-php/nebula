<?php

namespace Nebula\Framework\Alerts;

class Flash
{
    private static array $messages = [];

    /**
     * Add a flash message to the messages array
     * @param string $type (warning,danger,success,info,etc)
     * @param string $message
     */
    public static function add(string $type, string $message): void
    {
        self::$messages[strtolower($type)][] = $message;
    }

    /**
     * Get flash messages array
     * @return array
     */
    public static function get(): array
    {
        return self::$messages;
    }
}
