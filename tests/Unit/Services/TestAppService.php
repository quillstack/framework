<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit\Services;

use Quillstack\Framework\App;
use Quillstack\Framework\Services\AppEnvService;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestAppService
{
    public function __construct(private AssertBoolean $assertBoolean)
    {
        //
    }

    public function prodEnv()
    {
        $app = new App(
            dirname(__FILE__) . '/../../Fixtures/Dotenv/AppEnv/.env.prod'
        );
        $appService = $app->container->get(AppEnvService::class);

        $this->assertBoolean->isTrue($appService->isProduction());
        $this->assertBoolean->isTrue($appService->isEnv('production'));
        $this->assertBoolean->isTrue($appService->isEnv(['testing', 'production']));

        $this->assertBoolean->isFalse($appService->isDevelop());
        $this->assertBoolean->isFalse($appService->isTesting());
        $this->assertBoolean->isFalse($appService->isEnv(['testing', 'develop']));
    }
}
