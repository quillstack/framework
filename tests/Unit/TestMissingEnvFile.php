<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Exceptions\EnvFileNotFoundException;
use Quillstack\UnitTests\AssertExceptions;

class TestMissingEnvFile
{
    public function __construct(private AssertExceptions $assertExceptions)
    {
        //
    }

    public function missingEnvFileIsReported()
    {
        $this->assertExceptions->expect(EnvFileNotFoundException::class);

        new App(__DIR__ . '/../Fixtures/Dotenv/AppEnv/.env.missing');
    }
}
