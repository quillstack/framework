<?php

declare(strict_types=1);

namespace Quillstack\Framework;

use Psr\Http\Message\ResponseInterface;
use Quillstack\DI\Container;
use Quillstack\Dotenv\Dotenv;
use Quillstack\Framework\App\Config;
use Quillstack\Framework\App\Kernel;

class App
{
    public Container $container;

    public function __construct(string $envPath = '', array $config = [], private array $middleware = [])
    {
        $configWithEnv = $this->getConfigWithEnvPath($envPath, $config);
        $this->loadEnvIfRequired($configWithEnv);
        $this->container = new Container(
            (new Config())->loadEnv()->get($configWithEnv)
        );
    }

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

    private function loadEnvIfRequired(array $configWithEnv = []): void
    {
        $container = new Container(
            (new Config())->get($configWithEnv)
        );
        $dotenv = $container->get(Dotenv::class);
        $dotenv->load();
    }

    public function run(): ResponseInterface
    {
        $kernel = $this->container->get(Kernel::class);

        return $kernel->boot($this->container, $this->middleware);
    }
}
