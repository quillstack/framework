<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Console;

use Quillstack\Framework\Console\CommandInterface;
use Quillstack\Framework\Console\Input;
use Quillstack\Output\OutputInterface;

class GreetCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'greet';
    }

    public function getDescription(): string
    {
        return 'Says hello to somebody';
    }

    public function run(Input $input, OutputInterface $output): int
    {
        $name = $input->getArgument(0, 'world');
        $output->writeln("Hello, <green>{$name}</green>");

        if ($input->hasOption('twice')) {
            $output->writeln("Hello again, <green>{$name}</green>");
        }

        return 0;
    }
}
