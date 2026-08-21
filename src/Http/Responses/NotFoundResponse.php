<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses;

use Quillstack\HeaderBag\HeaderBag;
use Quillstack\Response\Response;
use Quillstack\Response\StatusCode;

/**
 * Answers a request matching no route. It used to go out as 200, telling every client that
 * a request which found nothing had succeeded.
 */
class NotFoundResponse extends Response
{
    public function __construct(?HeaderBag $headerBag = null)
    {
        parent::__construct(StatusCode::NOT_FOUND, '', $headerBag ?? new HeaderBag());
    }

    /**
     * {@inheritDoc}
     */
    public function send(): array
    {
        return [
            'error' => [
                'status' => $this->getStatusCode(),
                'message' => $this->getReasonPhrase(),
            ],
        ];
    }
}
