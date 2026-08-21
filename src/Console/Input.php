<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console;

/**
 * What was typed after the command name: values standing on their own, and options written
 * as `--name=value`, `--flag` or `-f`.
 */
class Input
{
    /**
     * @param string[] $arguments
     * @param array<string, string|bool> $options
     */
    public function __construct(
        private readonly string $command,
        private readonly array $arguments = [],
        private readonly array $options = []
    ) {
        //
    }

    /**
     * Reads a command line, the way PHP hands it over in $argv.
     *
     * @param string[] $argv
     */
    public static function fromArgv(array $argv): self
    {
        array_shift($argv);
        $command = 'list';
        $arguments = [];
        $options = [];

        foreach ($argv as $part) {
            if (str_starts_with($part, '--')) {
                $pieces = explode('=', substr($part, 2), 2);
                $options[$pieces[0]] = $pieces[1] ?? true;
            } elseif (str_starts_with($part, '-') && strlen($part) > 1) {
                foreach (str_split(substr($part, 1)) as $flag) {
                    $options[$flag] = true;
                }
            } elseif ($command === 'list' && $arguments === []) {
                $command = $part;
            } else {
                $arguments[] = $part;
            }
        }

        return new self($command, $arguments, $options);
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(int $index, ?string $default = null): ?string
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * @return array<string, string|bool>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }
}
