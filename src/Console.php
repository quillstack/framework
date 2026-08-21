<?php

declare(strict_types=1);

namespace Quillstack\Framework;

use Quillstack\DI\Container;
use Quillstack\Framework\App\Config;
use Quillstack\Framework\Console\ConsoleKernel;
use Quillstack\Framework\Console\Input;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;

/**
 * What App is for a request, this is for a command line: the same container, the same
 * configuration, and the same way of registering what the application brings.
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
        /** @var ConsoleKernel $kernel */
        $kernel = $this->container->get(ConsoleKernel::class);

        return $kernel->run(Input::fromArgv($argv));
    }

    private function isTerminal(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }
}
