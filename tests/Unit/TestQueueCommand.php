<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Psr\Container\ContainerInterface;
use Quillstack\Framework\Console;
use Quillstack\Framework\Tests\Mocks\Queue\SendEmail;
use Quillstack\Framework\Tests\Mocks\Queue\SendEmailHandler;
use Quillstack\Framework\Tests\Mocks\Queue\SentEmails;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;
use Quillstack\Queue\HandlerRegistry;
use Quillstack\Queue\Queue;
use Quillstack\Queue\Queues\ArrayQueue;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestQueueCommand
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{code: int, output: string, container: ContainerInterface}
     */
    private function run(array $argv, array $config = []): array
    {
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';

        $console = new Console('', $config + [
            OutputInterface::class => new Output(new Colors(), false),
        ]);

        ob_start();
        $code = $console->run($argv);

        return ['code' => $code, 'output' => (string) ob_get_clean(), 'container' => $console->container];
    }

    /**
     * Working a queue only makes sense where there is one.
     */
    public function withoutAQueueTheCommandIsNotThere()
    {
        $result = $this->run(['quill']);

        $this->assertBoolean->isFalse(str_contains($result['output'], 'queue:work'));
    }

    public function withAQueueTheCommandIsListed()
    {
        $handlers = new HandlerRegistry();
        $result = $this->run(['quill'], [
            Queue::class => new ArrayQueue(),
            HandlerRegistry::class => $handlers,
        ]);

        $this->assertBoolean->isTrue(str_contains($result['output'], 'queue:work'));
    }

    public function itHandlesWhatIsWaiting()
    {
        $queue = new ArrayQueue();
        $sent = new SentEmails();
        $handlers = (new HandlerRegistry())->handle(SendEmail::class, SendEmailHandler::class);

        $queue->push(new SendEmail('a@example.com'));
        $queue->push(new SendEmail('b@example.com'));

        $result = $this->run(['quill', 'queue:work'], [
            Queue::class => $queue,
            HandlerRegistry::class => $handlers,
            SentEmails::class => $sent,
        ]);

        $this->assertEqual->equal(0, $result['code']);
        $this->assertEqual->equal(['a@example.com', 'b@example.com'], $sent->addresses);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'handled 2'));
    }

    public function anamedQueueIsWorked()
    {
        $queue = new ArrayQueue();
        $sent = new SentEmails();
        $handlers = (new HandlerRegistry())->handle(SendEmail::class, SendEmailHandler::class);

        $queue->push(new SendEmail('a@example.com'), 'emails');

        $result = $this->run(['quill', 'queue:work', 'emails'], [
            Queue::class => $queue,
            HandlerRegistry::class => $handlers,
            SentEmails::class => $sent,
        ]);

        $this->assertEqual->equal(['a@example.com'], $sent->addresses);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'emails'));
    }
}
