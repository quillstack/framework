<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console;

interface CommandProviderInterface
{
    /**
     * The commands of the application, given as class names.
     *
     * @return array<int, class-string<CommandInterface>>
     */
    public function getCommands(): array;
}
