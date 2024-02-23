<?php

namespace VDB\Spider\Logging;

use Psr\Log\LoggerInterface;

trait LoggingTrait
{
    private ?LoggerInterface $logger = null;

    public function getLogger(): LoggerInterface
    {
        if ($this->logger == null) {
            $this->logger = DefaultLoggerFactory::getInstance();
        }
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
