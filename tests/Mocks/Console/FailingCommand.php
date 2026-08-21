<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Console;

use Quillstack\Framework\Console\CommandInterface;
use Quillstack\Framework\Console\Input;
use Quillstack\Output\OutputInterface;
use RuntimeException;

class FailingCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'boom';
    }

    public function getDescription(): string
    {
        return 'Throws on purpose';
    }

    public function run(Input $input, OutputInterface $output): int
    {
        throw new RuntimeException('the disk is full');
    }
}
