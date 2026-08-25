<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Psr\Http\Message\ServerRequestInterface;

final class AdminPersonController
{
    public function handle(ServerRequestInterface $request): AdminPersonResponse
    {
        return new AdminPersonResponse();
    }
}
