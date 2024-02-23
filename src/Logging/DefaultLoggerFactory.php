<?php

namespace VDB\Spider\Logging;

use Analog\Analog;
use Analog\Handler\EchoConsole;
use Analog\Handler\Multi;
use Analog\Handler\Stderr;
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

        $handler = Multi::init(array(
            Analog::WARNING => array(
                Stderr::init(),
                EchoConsole::init()
            ),
            Analog::DEBUG => EchoConsole::init(),
        ));
        $log->handler($handler);

        return $log;
    }
}
