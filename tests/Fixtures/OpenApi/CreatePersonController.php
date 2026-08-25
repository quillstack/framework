<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Validation\Attributes\Accepts;

final class CreatePersonController
{
    /**
     * Takes a person and keeps them.
     */
    #[Accepts([
        'email' => ['required', 'email'],
        'age' => ['required', 'integer', 'min:18'],
        'plan' => ['required', 'in:free,pro'],
        'name' => ['string', 'max:60'],
    ])]
    public function handle(ServerRequestInterface $request): PersonResponse
    {
        return new PersonResponse();
    }
}
