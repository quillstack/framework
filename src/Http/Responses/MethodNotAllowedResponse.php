<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses;

use Quillstack\HeaderBag\HeaderBag;
use Quillstack\Response\Response;
use Quillstack\Response\StatusCode;

/**
 * The path is known, the method is not. A 405 has to say which methods are allowed, which is
 * what makes it more useful to a client than the 404 this used to be.
 */
class MethodNotAllowedResponse extends Response
{
    public function __construct(?HeaderBag $headerBag = null)
    {
        parent::__construct(StatusCode::METHOD_NOT_ALLOWED, '', $headerBag ?? new HeaderBag());
    }

    /**
     * @param string[] $methods
     */
    public function withAllowedMethods(array $methods): self
    {
        /** @var self $response */
        $response = $this->withHeader('Allow', implode(', ', $methods));

        return $response;
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
                'allowed' => $this->getHeader('Allow'),
            ],
        ];
    }
}
