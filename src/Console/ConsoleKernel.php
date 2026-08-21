<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console;

use Psr\Container\ContainerInterface;
use Quillstack\Framework\Console\Commands\ListCommand;
use Quillstack\Framework\Console\Commands\QueueWorkCommand;
use Quillstack\Framework\Console\Exceptions\CommandNotFoundException;
use Quillstack\Framework\Services\AppEnvService;
use Quillstack\Output\OutputInterface;
use Quillstack\Queue\Queue;
use Throwable;

/**
 * Runs one command and answers with its exit code. What the routing middleware does for a
 * request, this does for a command line: find what was asked for, build it, run it, and turn
 * whatever went wrong into something the person typing can read.
 */
class ConsoleKernel
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly OutputInterface $output,
        private readonly AppEnvService $appEnv
    ) {
        //
    }

    public function run(Input $input): int
    {
        try {
            return $this->find($input->getCommand())->run($input, $this->output);
        } catch (CommandNotFoundException $exception) {
            $this->output->writeln("<red>{$exception->getMessage()}</red>");
            $this->output->writeln('Run <yellow>list</yellow> to see what there is.');

            return 1;
        } catch (Throwable $throwable) {
            return $this->reportFailure($throwable);
        }
    }

    /**
     * Every command the application has, plus the ones the framework brings, keyed by the
     * name each is typed as.
     *
     * @return array<string, CommandInterface>
     */
    public function getCommands(): array
    {
        /** @var CommandInterface[] $commands */
        $commands = [$this->container->get(ListCommand::class)];

        // Working a queue only makes sense where there is one, so the command shows up once
        // the application has configured it and not before.
        if ($this->container->has(Queue::class)) {
            /** @var CommandInterface $queueWork */
            $queueWork = $this->container->get(QueueWorkCommand::class);
            $commands[] = $queueWork;
        }

        if ($this->container->has(CommandProviderInterface::class)) {
            /** @var CommandProviderInterface $provider */
            $provider = $this->container->get(CommandProviderInterface::class);

            foreach ($provider->getCommands() as $class) {
                /** @var CommandInterface $command */
                $command = $this->container->get($class);
                $commands[] = $command;
            }
        }

        $byName = [];

        foreach ($commands as $command) {
            $byName[$command->getName()] = $command;
        }

        ksort($byName);

        return $byName;
    }

    private function find(string $name): CommandInterface
    {
        $commands = $this->getCommands();

        if (!isset($commands[$name])) {
            throw new CommandNotFoundException("There is no command called `{$name}`");
        }

        return $commands[$name];
    }

    /**
     * Says what went wrong. Outside production the exception is described as well, the same
     * way a request is answered.
     */
    private function reportFailure(Throwable $throwable): int
    {
        $this->output->writeln("<red>{$throwable->getMessage()}</red>");

        if (!$this->appEnv->isProduction()) {
            $class = $throwable::class;
            $where = $throwable->getFile() . ':' . $throwable->getLine();

            $this->output->writeln("<dark-grey>{$class} in {$where}</dark-grey>");
            $this->output->writeln("<dark-grey>{$throwable->getTraceAsString()}</dark-grey>");
        }

        return 1;
    }
}
