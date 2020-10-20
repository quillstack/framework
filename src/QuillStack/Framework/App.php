<?php

declare(strict_types=1);

namespace QuillStack\Framework;

use Psr\Http\Message\ResponseInterface;
use QuillStack\DI\Container;
use QuillStack\Dotenv\Dotenv;
use QuillStack\Framework\App\Config;
use QuillStack\Framework\App\Kernel;

final class App
{
    /**
     * @var Container
     */
    public Container $container;

    /**
     * @var array
     */
    private array $middleware;

    /**
     * @param string $envPath
     * @param array $config
     * @param array $middleware
     */
    public function __construct(string $envPath = '', array $config = [], array $middleware = [])
    {
        $configWithEnv = $this->getConfigWithEnvPath($envPath, $config);
        $this->loadEnvIfRequired($configWithEnv);
        $this->middleware = $middleware;
        $this->container = new Container(
            (new Config())->get($configWithEnv)
        );
    }

    /**
     * @param string $envPath
     * @param array $config
     *
     * @return array
     */
    private function getConfigWithEnvPath(string $envPath, array $config = []): array
    {
        if (empty($envPath)) {
            return $config;
        }

        return array_merge([
            Dotenv::class => [
                'path' => $envPath,
            ],
        ], $config);
    }

    /**
     * @param array $configWithEnv
     */
    private function loadEnvIfRequired(array $configWithEnv = []): void
    {
        $container = new Container(
            (new Config())->get($configWithEnv)
        );
        $dotenv = $container->get(Dotenv::class);
        $dotenv->load();
    }

    /**
     * @return ResponseInterface
     */
    public function run(): ResponseInterface
    {
        $kernel = $this->container->get(Kernel::class);

        return $kernel->boot($this->container, $this->middleware);
    }
}
