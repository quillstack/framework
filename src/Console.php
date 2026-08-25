<?php

declare(strict_types=1);

namespace Quillstack\Framework;

use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\ConsoleKernel;
use Quillstack\Cli\Input;
use Quillstack\DI\Container;
use Quillstack\Framework\Console\Commands\MigrateCommand;
use Quillstack\Framework\Console\Commands\OpenApiCommand;
use Quillstack\Framework\Console\Commands\QueueWorkCommand;
use Quillstack\Framework\Database\EntityRegistryInterface;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Services\AppEnvService;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;
use Quillstack\Queue\Queue;

/**
 * What App is for a request, this is for a command line: the same container, the same
 * configuration, and the same way of registering what the application brings.
 *
 * The command line itself is quillstack/cli, which knows nothing about queues or entities.
 * What belongs to the framework is decided here.
 */
class Console
{
    public Container $container;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $envPath = '', array $config = [])
    {
        $app = new App($envPath, $config);
        $this->container = $app->container;

        // Escape codes belong on a terminal and nowhere else, so a command whose output is
        // piped into a file writes the text alone.
        $this->container->addToConfig([
            OutputInterface::class => new Output(new Colors(), $this->isTerminal()),
        ]);
    }

    /**
     * Runs the command line and returns the exit code, ready for `exit()`.
     *
     * @param string[] $argv
     */
    public function run(array $argv): int
    {
        return $this->kernel()->run(Input::fromArgv($argv));
    }

    /**
     * The kernel, with the framework's own commands on it.
     */
    public function kernel(): ConsoleKernel
    {
        /** @var AppEnvService $appEnv */
        $appEnv = $this->container->get(AppEnvService::class);

        // Outside production the exception is described as well, the same way a request is
        // answered — on a server that is not something a person typing should be shown.
        $this->container->addToConfig([
            ConsoleKernel::class => ['describeFailures' => !$appEnv->isProduction()],
        ]);

        /** @var ConsoleKernel $kernel */
        $kernel = $this->container->get(ConsoleKernel::class);

        return $kernel->add(...$this->commands());
    }

    /**
     * The framework's own commands, each where it applies.
     *
     * Working a queue only makes sense where there is one, migrating only where the
     * application has said what its entities are, and describing an API only where there are
     * routes to describe — so each shows up once it does and not before. The container can
     * answer that because `has()` says what it means.
     *
     * @return array<int, class-string<CommandInterface>>
     */
    private function commands(): array
    {
        $commands = [];

        if ($this->container->has(Queue::class)) {
            $commands[] = QueueWorkCommand::class;
        }

        if ($this->container->has(EntityRegistryInterface::class)) {
            $commands[] = MigrateCommand::class;
        }

        if ($this->container->has(RouteProviderInterface::class)) {
            $commands[] = OpenApiCommand::class;
        }

        return $commands;
    }

    private function isTerminal(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }
}
