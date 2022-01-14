<?php

declare(strict_types=1);

namespace QuillStack\Framework\Providers;

use QuillStack\Config\ConfigProviderInterface;
use QuillStack\Framework\Config\LoggerConfig;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array
     */
    protected array $config = [
        'logger' => LoggerConfig::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function load(): array
    {
        return $this->config;
    }
}
