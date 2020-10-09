<?php

declare(strict_types=1);

namespace QuillStack\Framework;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use QuillStack\DI\Container;
use QuillStack\Framework\App\Kernel;
use QuillStack\Http\Stream\InputStream;
use QuillStack\Http\Uri\Factory\UriFactory;

final class App
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->container = new Container(array_merge($config, [
            StreamInterface::class => InputStream::class,
            UriFactoryInterface::class => UriFactory::class,
        ]));
    }

    /**
     * @return string
     */
    public function run(): string
    {
        $kernel = $this->container->get(Kernel::class);
        $response = $kernel->boot($this->container);

        return json_encode($response);
    }
}
