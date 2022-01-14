<?php

declare(strict_types=1);

namespace QuillStack\Framework;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use QuillStack\MonologFactory\FileLoggerClassFactory;

final class LoggerLevelEnvTest extends TestCase
{
    public function testLogLevelFromEnv()
    {
        $app = new App(
            dirname(__FILE__) . '/../Fixtures/Dotenv/.env.logger'
        );
        $monologFactory = $app->container->get(FileLoggerClassFactory::class);
        $level = env('APP_LOG_LEVEL');

        $this->assertEquals(Logger::toMonologLevel($level), $monologFactory->level);
        $this->assertStringEndsWith('var/logs/custom_name.log', $monologFactory->path);
    }
}
