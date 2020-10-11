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
     * @param array $config
     */
    public function __construct(array $config = [])
    {
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
        $response = $kernel->boot($this->container);

        return json_encode($response);
    }
}
