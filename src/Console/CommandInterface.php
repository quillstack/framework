<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console;

use Quillstack\Output\OutputInterface;

interface CommandInterface
{
    /**
     * How the command is typed, e.g. `cache:clear`.
     */
    public function getName(): string;

    /**
     * One line saying what it does, shown in the list of commands.
     */
    public function getDescription(): string;

    /**
     * The exit code: 0 when it worked.
     */
    public function run(Input $input, OutputInterface $output): int;
}
