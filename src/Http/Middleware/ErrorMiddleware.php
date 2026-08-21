<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Quillstack\Framework\Exceptions\Http\HttpException;
use Quillstack\Framework\Http\Responses\ErrorResponse;
use Quillstack\Framework\Services\AppEnvService;
use Quillstack\Response\StatusCode;
use Throwable;

/**
 * The outermost middleware, so nothing thrown further in reaches the client as a fatal
 * error and a stack trace. An HttpException answers with the status it carries, anything
 * else is an error of the application and answers with 500.
 */
class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly AppEnvService $appEnv
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException $exception) {
            return $this->respond($exception->getStatusCode(), $exception->getMessage(), $exception);
        } catch (Throwable $throwable) {
            return $this->respond(StatusCode::INTERNAL_SERVER_ERROR, '', $throwable);
        }
    }

    private function respond(int $status, string $message, Throwable $throwable): ResponseInterface
    {
        if ($status >= StatusCode::INTERNAL_SERVER_ERROR) {
            $this->log($throwable);
        }

        $response = (new ErrorResponse($status))->setError($message);

        // What went wrong is only described where somebody is working on it. In production
        // the client is told the status and nothing about the internals.
        if (!$this->appEnv->isProduction()) {
            $response->describe($throwable);
        }

        return $response->withHeader('Content-Type', 'text/json');
    }

    /**
     * Writes to the logger when the application configured one.
     */
    private function log(Throwable $throwable): void
    {
        if (!$this->container->has(LoggerInterface::class)) {
            return;
        }

        $this->container->get(LoggerInterface::class)->error($throwable->getMessage(), [
            'exception' => $throwable::class,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);
    }
}
