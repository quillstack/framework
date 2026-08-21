<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses;

use Quillstack\HeaderBag\HeaderBag;
use Quillstack\Response\Response;
use Quillstack\Response\StatusCode;

/**
 * A response carrying a status and nothing else, for the answers which are the status.
 */
class EmptyResponse extends Response
{
    public function __construct(int $code = StatusCode::NO_CONTENT, ?HeaderBag $headerBag = null)
    {
        parent::__construct($code, '', $headerBag ?? new HeaderBag());
    }

    /**
     * {@inheritDoc}
     */
    public function send(): array
    {
        return [];
    }
}
