<?php

namespace VDB\Spider\Logging;

use Analog\Logger;
use Psr\Log\LoggerInterface;

class DefaultLoggerFactory
{
    private static LoggerInterface $logger;

    public static function getInstance(): LoggerInterface
    {
        if (!isset(self::$logger)) {
            self::$logger = self::createDefaultLogger();
        }
        return self::$logger;
    }

    private static function createDefaultLogger(): LoggerInterface
    {
        $log = new Logger();
        $log->handler(new DefaultLogHandler());
        $log->format("\n" . '%2$s - [%3$s] - %4$s');
        return $log;
    }
}
