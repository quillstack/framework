<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Responses;

use Quillstack\Response\Response;

class VersionResponse extends Response
{
    private string $version = '';

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): array
    {
        return [
            'version' => $this->version,
        ];
    }
}
