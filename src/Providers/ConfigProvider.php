<?php

declare(strict_types=1);

namespace Quillstack\Framework\Providers;

use Quillstack\Config\ConfigProviderInterface;
use Quillstack\Framework\Config\LoggerConfig;

class ConfigProvider implements ConfigProviderInterface
{
    protected array $config = [
        'logger' => LoggerConfig::class,
    ];

    public function load(): array
    {
        return $this->config;
    }
}
