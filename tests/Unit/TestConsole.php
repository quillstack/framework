<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\Console;
use Quillstack\Framework\Console\CommandProviderInterface;
use Quillstack\Framework\Tests\Mocks\Console\CommandProvider;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestConsole
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * @param string[] $argv
     *
     * @return array{code: int, output: string}
     */
    private function run(array $argv, bool $withCommands = true, string $env = 'production'): array
    {
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $env;

        $config = [
            // Undecorated, so a test compares the text and not the escape codes.
            OutputInterface::class => new Output(new Colors(), false),
        ];

        if ($withCommands) {
            $config[CommandProviderInterface::class] = CommandProvider::class;
        }

        $console = new Console('', $config);

        ob_start();
        $code = $console->run($argv);

        return ['code' => $code, 'output' => (string) ob_get_clean()];
    }

    public function aCommandRuns()
    {
        $result = $this->run(['quill', 'greet', 'Radek']);

        $this->assertEqual->equal(0, $result['code']);
        $this->assertEqual->equal('Hello, Radek' . PHP_EOL, $result['output']);
    }

    public function anArgumentHasADefault()
    {
        $this->assertEqual->equal('Hello, world' . PHP_EOL, $this->run(['quill', 'greet'])['output']);
    }

    public function optionsAreRead()
    {
        $result = $this->run(['quill', 'greet', 'Radek', '--twice']);

        $this->assertEqual->equal(
            'Hello, Radek' . PHP_EOL . 'Hello again, Radek' . PHP_EOL,
            $result['output']
        );
    }

    public function nothingTypedListsTheCommands()
    {
        $result = $this->run(['quill']);

        $this->assertEqual->equal(0, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'greet'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'Says hello to somebody'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'list'));
    }

    public function anApplicationWithoutCommandsStillLists()
    {
        $result = $this->run(['quill'], false);

        $this->assertEqual->equal(0, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'list'));
    }

    public function aCommandNobodyKnowsIsReported()
    {
        $result = $this->run(['quill', 'nonsense']);

        $this->assertEqual->equal(1, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'There is no command called `nonsense`'));
    }

    /**
     * A command which throws answers with an exit code, so a script calling it can tell.
     */
    public function aFailingCommandExitsWithOne()
    {
        $result = $this->run(['quill', 'boom']);

        $this->assertEqual->equal(1, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'the disk is full'));
        $this->assertBoolean->isFalse(str_contains($result['output'], 'RuntimeException'));
    }

    public function outsideProductionTheFailureIsDescribed()
    {
        $result = $this->run(['quill', 'boom'], true, 'develop');

        $this->assertBoolean->isTrue(str_contains($result['output'], 'RuntimeException'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'FailingCommand.php'));
    }
}
