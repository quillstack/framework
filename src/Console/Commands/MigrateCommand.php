<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console\Commands;

use Psr\Container\ContainerInterface;
use Quillstack\Framework\Console\CommandInterface;
use Quillstack\Framework\Console\Input;
use Quillstack\Framework\Database\EntityRegistryInterface;
use Quillstack\Orm\Migration\Migrator;
use Quillstack\Orm\Migration\Plan;
use Quillstack\Output\OutputInterface;

/**
 * Brings the database to what the entities say it should be.
 *
 * Nothing is applied without being shown first, and `--pretend` stops before applying it —
 * a migration is worth looking at before it happens.
 */
class MigrateCommand implements CommandInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Migrator $migrator
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'db:migrate';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Brings the database to what the entities describe';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        $plan = $this->migrator->plan($this->entities());

        $this->describe($plan, $output);

        if ($plan->isEmpty()) {
            $output->writeln('<green>Nothing to do.</green>');

            return 0;
        }

        if ($input->hasOption('pretend')) {
            $output->writeln('<yellow>Nothing was run.</yellow> Drop --pretend to apply it.');

            return 0;
        }

        $count = $this->migrator->apply($plan);
        $output->writeln("<green>Ran {$count}.</green>");

        return 0;
    }

    private function describe(Plan $plan, OutputInterface $output): void
    {
        foreach ($plan->statements as $statement) {
            foreach (explode("\n", $statement) as $line) {
                $output->writeln("  <dark-grey>{$line}</dark-grey>");
            }
        }

        foreach ($plan->warnings as $warning) {
            $output->writeln("<yellow>!</yellow> {$warning}");
        }
    }

    /**
     * @return array<int, class-string>
     */
    private function entities(): array
    {
        if (!$this->container->has(EntityRegistryInterface::class)) {
            return [];
        }

        /** @var EntityRegistryInterface $registry */
        $registry = $this->container->get(EntityRegistryInterface::class);

        return $registry->getEntities();
    }
}
