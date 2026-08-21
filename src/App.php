<?php

declare(strict_types=1);

namespace Quillstack\Framework;

use Psr\Http\Message\ResponseInterface;
use Quillstack\DI\Container;
use Quillstack\Dotenv\Dotenv;
use Quillstack\Framework\App\Config;
use Quillstack\Framework\App\Kernel;
use Quillstack\Framework\Exceptions\EnvFileNotFoundException;

class App
{
    public Container $container;

    /**
     * @param array<string, mixed> $config
     * @param array<int, class-string> $middleware
     */
    public function __construct(string $envPath = '', array $config = [], private array $middleware = [])
    {
        $configWithEnv = $this->getConfigWithEnvPath($envPath, $config);
        $this->loadEnvIfRequired($envPath, $configWithEnv);
        $this->container = new Container(
            (new Config())->get($configWithEnv)
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
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
     * @param array<string, mixed> $configWithEnv
     */
    private function loadEnvIfRequired(string $envPath, array $configWithEnv = []): void
    {
        if ($envPath !== '' && !is_file($envPath)) {
            throw new EnvFileNotFoundException(
                "Environment file not found: {$envPath}. Copy `.env.example` to `.env` to create it.",
                500
            );
        }

        $container = new Container(
            (new Config())->get($configWithEnv)
        );

        /** @var Dotenv $dotenv */
        $dotenv = $container->get(Dotenv::class);
        $dotenv->load();
    }

    public function run(): ResponseInterface
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get(Kernel::class);

        return $kernel->boot($this->middleware);
    }
}
