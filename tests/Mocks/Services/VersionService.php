<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Services;

class VersionService
{
    public function getVersion(): string
    {
        return '1.0.1';
    }
}
