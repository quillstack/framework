<?php

declare(strict_types=1);

namespace Quillstack\Framework\App;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Quillstack\Config\ConfigProviderInterface;
use Quillstack\Framework\InstanceFactories\RequestClassFactory;
use Quillstack\Framework\Interfaces\RequestInterface;
use Quillstack\Framework\Providers\ConfigProvider;
use Quillstack\Middleware\Defaults\AuthorizationMiddleware;
use Quillstack\Middleware\Defaults\JsonResponseMiddleware;
use Quillstack\Middleware\Defaults\RoutingMiddleware;
use Quillstack\Middleware\Defaults\TrimStringsMiddleware;
use Quillstack\Stream\InputStream;
use Quillstack\Uri\Factory\UriFactory;

class Config
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

    private array $defaultConfig = [
        StreamInterface::class => InputStream::class,
        UriFactoryInterface::class => UriFactory::class,
        RequestInterface::class => RequestClassFactory::class,
        ConfigProviderInterface::class => ConfigProvider::class,
    ];

    public string $root;
    private array $envConfig = [];

    public function __construct()
    {
        $this->root = dirname(__FILE__) . '/../../../../../../../';
    }

    public function loadEnv(): self
    {
        $this->envConfig = [];

        return $this;
    }

    public function get(array $config): array
    {
        return array_merge($this->defaultConfig, $this->envConfig, $config);
    }
}
