<?php

declare(strict_types=1);

return [
    \Quillstack\Framework\Tests\Unit\TestNotFound::class,
    \Quillstack\Framework\Tests\Unit\TestErrorHandling::class,
    \Quillstack\Framework\Tests\Unit\TestErrorLogging::class,
    \Quillstack\Framework\Tests\Unit\TestSimpleMiddleware::class,
    \Quillstack\Framework\Tests\Unit\TestSimpleService::class,
    \Quillstack\Framework\Tests\Unit\TestSimpleRequest::class,
    \Quillstack\Framework\Tests\Unit\TestRouteParameters::class,
    \Quillstack\Framework\Tests\Unit\TestResponseHeaders::class,
    \Quillstack\Framework\Tests\Unit\TestMissingEnvFile::class,

    \Quillstack\Framework\Tests\Unit\Services\TestAppService::class,
];
