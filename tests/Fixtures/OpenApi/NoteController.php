<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Psr\Http\Message\ServerRequestInterface;

final class NoteController
{
    public function handle(ServerRequestInterface $request): NoteResponse
    {
        return new NoteResponse();
    }
}
