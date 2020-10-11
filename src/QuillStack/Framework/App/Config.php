<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use QuillStack\Framework\InstanceFactories\RequestClassFactory;
use QuillStack\Framework\Interfaces\RequestInterface;
use QuillStack\Http\Stream\InputStream;
use QuillStack\Http\Uri\Factory\UriFactory;
use QuillStack\MonologFactory\FileLoggerClassFactory;

final class Config
{
    /**
     * @var array
     */
    public const DEFAULT_CONFIG = [
        StreamInterface::class => InputStream::class,
        UriFactoryInterface::class => UriFactory::class,
        RequestInterface::class => RequestClassFactory::class,
        LoggerInterface::class => FileLoggerClassFactory::class,
    ];
}