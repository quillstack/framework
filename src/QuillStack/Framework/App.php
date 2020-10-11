<?php

declare(strict_types=1);

namespace QuillStack\Framework;

use QuillStack\DI\Container;
use QuillStack\Framework\App\Config;
use QuillStack\Framework\App\Kernel;

final class App
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var array
     */
    private array $middleware;

    /**
     * @param array $config
     * @param array $middleware
     */
    public function __construct(array $config = [], array $middleware = [])
    {
        $this->middleware = $middleware;
        $this->container = new Container(
            array_merge(Config::DEFAULT_CONFIG, $config)
        );
    }

    /**
     * @return string
     */
    public function run(): string
    {
        $kernel = $this->container->get(Kernel::class);
        $response = $kernel->boot($this->container, $this->middleware);

        return json_encode($response);
    }
}
