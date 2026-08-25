<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Quillstack\Framework\Exceptions\Http\BadRequestHttpException;
use Quillstack\Framework\Http\Middleware\JsonBodyMiddleware;
use Quillstack\Framework\Http\Responses\EmptyResponse;
use Quillstack\HeaderBag\HeaderBag;
use Quillstack\ServerRequest\ServerRequest;
use Quillstack\Uri\Uri;
use Quillstack\Stream\TextStream;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * PHP fills `$_POST` from a form-encoded body and from nothing else, so a framework built for
 * APIs was handed nothing at all by every client that sent it JSON. The body was not rejected,
 * it was absent — and what came back was a validation error naming fields the caller had sent.
 *
 * It went unnoticed because nothing in the skeleton had a request body to send.
 */
class TestJsonBody
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
    ) {
        //
    }

    private function request(string $type, string $body): ServerRequest
    {
        return new ServerRequest(
            'POST',
            new Uri('http://localhost/users'),
            '1.1',
            new HeaderBag(['Content-Type' => [$type]]),
            new TextStream($body)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function through(ServerRequestInterface $request): array
    {
        $seen = [];

        $handler = new class ($seen) implements RequestHandlerInterface {
            /**
             * @param array<string, mixed> $seen
             */
            public function __construct(private array &$seen)
            {
                //
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->seen = (array) $request->getParsedBody();

                return new EmptyResponse();
            }
        };

        (new JsonBodyMiddleware())->process($request, $handler);

        return $seen;
    }

    public function aJsonBodyReachesTheApplication()
    {
        $parsed = $this->through($this->request('application/json', '{"email":"radek@quillstack.com","age":41}'));

        $this->assertEqual->equal('radek@quillstack.com', $parsed['email']);
        $this->assertEqual->equal(41, $parsed['age']);
    }

    /**
     * `application/vnd.thing+json` is JSON, and an API which only knows the one spelling turns
     * a caller following a convention into a caller sending nothing.
     */
    public function aSuffixedJsonTypeIsStillJson()
    {
        $parsed = $this->through($this->request('application/vnd.quillstack.v1+json', '{"email":"a@example.com"}'));

        $this->assertEqual->equal('a@example.com', $parsed['email']);
    }

    /**
     * A charset is part of the header and not part of the question.
     */
    public function aCharsetDoesNotHideIt()
    {
        $parsed = $this->through($this->request('application/json; charset=utf-8', '{"email":"a@example.com"}'));

        $this->assertEqual->equal('a@example.com', $parsed['email']);
    }

    /**
     * Broken JSON answered as an empty body sends the caller looking for a missing field they
     * did in fact send. It is a 400, and it says which.
     */
    public function brokenJsonIsRefusedRatherThanTreatedAsNothing()
    {
        $this->assertExceptions->expect(BadRequestHttpException::class);

        $this->through($this->request('application/json', '{"email":,}'));
    }

    public function anEmptyBodyIsNotBrokenJson()
    {
        $this->assertEqual->equal([], $this->through($this->request('application/json', '')));
    }

    /**
     * A form-encoded body is PHP's business and is already where it belongs, so this leaves it
     * alone rather than reading it a second time.
     */
    public function aFormBodyIsLeftAlone()
    {
        $parsed = $this->through($this->request('application/x-www-form-urlencoded', 'email=a@example.com'));

        $this->assertEqual->equal([], $parsed);
    }

    /**
     * Valid JSON which is not an object has nowhere to go in a parsed body, so it stays in the
     * body rather than being forced into one.
     */
    public function aJsonListIsLeftInTheBody()
    {
        $this->assertEqual->equal([], $this->through($this->request('application/json', '[1,2,3]')));
    }
}
