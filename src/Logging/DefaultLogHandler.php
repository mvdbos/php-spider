<?php

namespace VDB\Spider\Logging;

use Analog\Analog;
use Analog\Handler\EchoConsole;
use Analog\Handler\Stderr;

/**
 * This logger. It logs DEBUG and higher to the console and WARNING and higher to stderr.
 */
class DefaultLogHandler
{
    private array $_handlers;

    public function __construct()
    {
        $this->_handlers = array(
            Analog::WARNING => Stderr::init(),
            Analog::DEBUG => EchoConsole::init(),
        );
    }

    public function log($info): void
    {
        $level = is_numeric($info['level']) ? $info['level'] : Analog::ERROR;
        while ($level <= Analog::DEBUG) {
            if (isset ($this->_handlers[$level])) {
                $this->_handlers[$level]($info);
                break;
            }
            $level++;
        }
    }
}