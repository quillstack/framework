<?php

declare(strict_types=1);

namespace QuillStack\Framework\Services;

use PHPUnit\Framework\TestCase;
use QuillStack\Framework\App;

final class AppServiceTest extends TestCase
{
    public function testProdEnv()
    {
        $app = new App(
            dirname(__FILE__) . '/../../Fixtures/Dotenv/AppEnv/.env.prod'
        );
        $appService = $app->container->get(AppEnvService::class);

        $this->assertTrue($appService->isProduction());
        $this->assertTrue($appService->isEnv('production'));
        $this->assertTrue($appService->isEnv(['testing', 'production']));

        $this->assertFalse($appService->isDevelop());
        $this->assertFalse($appService->isTesting());
        $this->assertFalse($appService->isEnv(['testing', 'develop']));
    }
}
