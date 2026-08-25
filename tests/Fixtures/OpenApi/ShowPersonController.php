<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Psr\Http\Message\ServerRequestInterface;

final class ShowPersonController
{
    public function handle(ServerRequestInterface $request): PersonResponse
    {
        return new PersonResponse();
    }
}
