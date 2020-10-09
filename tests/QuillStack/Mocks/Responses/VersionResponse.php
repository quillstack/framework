<?php

declare(strict_types=1);

namespace QuillStack\Mocks\Responses;

use QuillStack\Http\Response\Response;

final class VersionResponse extends Response
{
    /**
     * {@inheritDoc}
     */
    public function send(): array
    {
        return [
            'version' => '1.0.0',
        ];
    }
}
