<?php

declare(strict_types=1);

namespace QuillStack\Mocks\Services;

final class VersionService
{
    /**
     * @return string
     */
    public function getVersion(): string
    {
        return '1.0.1';
    }
}
