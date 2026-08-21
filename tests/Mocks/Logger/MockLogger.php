<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

class MockLogger extends AbstractLogger
{
    public array $entries = [];

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }
}
