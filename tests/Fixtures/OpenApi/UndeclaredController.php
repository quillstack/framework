<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Psr\Http\Message\ServerRequestInterface;

final class UndeclaredController
{
    public function handle(ServerRequestInterface $request): UndeclaredResponse
    {
        return new UndeclaredResponse();
    }
}
