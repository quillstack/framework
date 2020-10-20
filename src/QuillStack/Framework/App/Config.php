<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Monolog\Logger;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use QuillStack\Framework\InstanceFactories\RequestClassFactory;
use QuillStack\Framework\Interfaces\RequestInterface;
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
    ];

    /**
     * @var string
     */
    public string $root;

    /**
     * @var array
     */
    private array $envConfig;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->root = dirname(__FILE__) . '/../../../../../../../';
        $this->envConfig = [
            FileLoggerClassFactory::class => [
                'level' => Logger::toMonologLevel(env('APP_LOG_LEVEL', 'warning')),
                'path' => "{$this->root}var/logs/" . env('APP_LOG_FILENAME', 'quillstack.log'),
            ],
        ];
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
