<?php

declare(strict_types=1);

namespace QuillStack\Framework\Config;

use QuillStack\Config\Config;

final class LoggerConfig extends Config
{
    public function __construct()
    {
        $this->config = [
            'level' => env('APP_LOG_LEVEL', 'warning'),
            'filename' => env('APP_LOG_FILENAME', 'quillstack.log'),
        ];
    }
}
