<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Monolog\Logger;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use QuillStack\Config\ConfigProviderInterface;
use QuillStack\Framework\InstanceFactories\RequestClassFactory;
use QuillStack\Framework\Interfaces\RequestInterface;
use QuillStack\Framework\Providers\ConfigProvider;
use QuillStack\Http\Stream\InputStream;
use QuillStack\Http\Uri\Factory\UriFactory;
use QuillStack\Middleware\Defaults\AuthorizationMiddleware;
use QuillStack\Middleware\Defaults\JsonResponseMiddleware;
use QuillStack\Middleware\Defaults\RoutingMiddleware;
use QuillStack\Middleware\Defaults\TrimStringsMiddleware;
use QuillStack\MonologFactory\FileLoggerClassFactory;

final class Config
{
    /**
     * @var array
     */
    public const DEFAULT_MIDDLEWARE = [
        RoutingMiddleware::class,
        JsonResponseMiddleware::class,
        TrimStringsMiddleware::class,
        AuthorizationMiddleware::class,
    ];

    /**
     * @var array
     */
    private array $defaultConfig = [
        StreamInterface::class => InputStream::class,
        UriFactoryInterface::class => UriFactory::class,
        RequestInterface::class => RequestClassFactory::class,
        LoggerInterface::class => FileLoggerClassFactory::class,
        ConfigProviderInterface::class => ConfigProvider::class,
    ];

    /**
     * @var string
     */
    public string $root;

    /**
     * @var array
     */
    private array $envConfig = [];

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->root = dirname(__FILE__) . '/../../../../../../../';
    }

    /**
     * @return Config
     */
    public function loadEnv(): self
    {
        $this->envConfig = [
            FileLoggerClassFactory::class => [
                'level' => Logger::toMonologLevel(config('logger.level')),
                'path' => "{$this->root}var/logs/" . config('logger.filename'),
            ],
        ];

        return $this;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function get(array $config): array
    {
        return array_merge($this->defaultConfig, $this->envConfig, $config);
    }
}
