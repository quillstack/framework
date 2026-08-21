<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console\Commands;

use Quillstack\Framework\Console\CommandInterface;
use Quillstack\Framework\Console\Input;
use Quillstack\Output\OutputInterface;
use Quillstack\Queue\Queue;
use Quillstack\Queue\Worker;

/**
 * Handles what is waiting on a queue. Run once it takes everything due and stops, which is
 * what a scheduled run wants; with `--keep-running` it waits for more.
 */
class QueueWorkCommand implements CommandInterface
{
    public function __construct(private readonly Worker $worker)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'queue:work';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Handles the messages waiting on a queue';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        $queue = (string) ($input->getArgument(0) ?? Queue::DEFAULT);
        $sleep = max(1, (int) $input->getOption('sleep', '1'));

        $output->writeln("Working the <yellow>{$queue}</yellow> queue");

        do {
            $handled = $this->worker->runAll($queue);
            $output->writeln("  handled <green>{$handled}</green>");

            if ($input->hasOption('keep-running')) {
                sleep($sleep);
            }
        } while ($input->hasOption('keep-running'));

        return 0;
    }
}
