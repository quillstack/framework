<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Db\Connection;
use Quillstack\Framework\Console;
use Quillstack\Framework\Database\EntityRegistryInterface;
use Quillstack\Framework\Tests\Mocks\Entities\EntityRegistry;
use Quillstack\Orm\Migration\Migrator;
use Quillstack\Orm\Orm;
use Quillstack\Orm\Schema\Introspection\SqliteIntrospector;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestMigrateCommand
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * @param string[] $argv
     * @param array<string, mixed> $config
     *
     * @return array{code: int, output: string}
     */
    private function run(array $argv, array $config = []): array
    {
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';

        $console = new Console('', $config + [
            OutputInterface::class => new Output(new Colors(), false),
        ]);

        ob_start();
        $code = $console->run($argv);

        return ['code' => $code, 'output' => (string) ob_get_clean()];
    }

    /**
     * @return array<string, mixed>
     */
    private function withDatabase(Connection $connection): array
    {
        return [
            Connection::class => $connection,
            Migrator::class => new Migrator($connection),
            Orm::class => new Orm($connection),
            EntityRegistryInterface::class => EntityRegistry::class,
        ];
    }

    /**
     * Migrating only makes sense where the application has said what its entities are.
     */
    public function withoutEntitiesTheCommandIsNotThere()
    {
        $this->assertBoolean->isFalse(
            str_contains($this->run(['quill'])['output'], 'db:migrate')
        );
    }

    public function withEntitiesTheCommandIsListed()
    {
        $result = $this->run(['quill'], $this->withDatabase(new Connection('sqlite::memory:')));

        $this->assertBoolean->isTrue(str_contains($result['output'], 'db:migrate'));
    }

    public function itBuildsWhatTheEntitiesDescribe()
    {
        $connection = new Connection('sqlite::memory:');
        $result = $this->run(['quill', 'db:migrate'], $this->withDatabase($connection));

        $this->assertEqual->equal(0, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'CREATE TABLE'));
        $this->assertEqual->equal(['notes'], (new SqliteIntrospector($connection))->tables());
    }

    /**
     * A migration is worth looking at before it happens.
     */
    public function pretendingShowsItWithoutRunningIt()
    {
        $connection = new Connection('sqlite::memory:');
        $result = $this->run(['quill', 'db:migrate', '--pretend'], $this->withDatabase($connection));

        $this->assertBoolean->isTrue(str_contains($result['output'], 'CREATE TABLE'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'Nothing was run'));
        $this->assertEqual->equal([], (new SqliteIntrospector($connection))->tables());
    }

    /**
     * Running it again finds nothing to do, which is what makes it safe on every deploy.
     */
    public function runningItTwiceSaysThereIsNothingToDo()
    {
        $connection = new Connection('sqlite::memory:');
        $config = $this->withDatabase($connection);

        $this->run(['quill', 'db:migrate'], $config);
        $result = $this->run(['quill', 'db:migrate'], $config);

        $this->assertBoolean->isTrue(str_contains($result['output'], 'Nothing to do'));
    }
}
