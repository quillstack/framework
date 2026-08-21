<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console\Commands;

use Psr\Container\ContainerInterface;
use Quillstack\Framework\Console\CommandInterface;
use Quillstack\Framework\Console\ConsoleKernel;
use Quillstack\Framework\Console\Input;
use Quillstack\Output\OutputInterface;

/**
 * What runs when nothing was asked for: everything there is to ask for.
 */
class ListCommand implements CommandInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'list';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Lists the commands there are';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        /** @var ConsoleKernel $kernel */
        $kernel = $this->container->get(ConsoleKernel::class);
        $commands = $kernel->getCommands();
        $width = max(1, ...array_map('strlen', array_keys($commands)));

        $output->writeln('<green>Commands</green>');

        foreach ($commands as $name => $command) {
            $padded = str_pad($name, $width);
            $output->writeln("  <yellow>{$padded}</yellow>  {$command->getDescription()}");
        }

        return 0;
    }
}
