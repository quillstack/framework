<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Quillstack\Auth\Exceptions\AuthException;
use Quillstack\Framework\Exceptions\Http\HttpException;
use Quillstack\Framework\Http\Responses\ErrorResponse;
use Quillstack\Framework\Services\AppEnvService;
use Quillstack\Framework\Validation\Exceptions\ValidationFailedException;
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
        } catch (ValidationFailedException $exception) {
            return $this->respond(
                $exception->getStatusCode(),
                $exception->getMessage(),
                $exception,
                ['fields' => $exception->getErrors()]
            );
        } catch (AuthException $exception) {
            // Refusing is not an error of the application: it is the answer. Nothing about
            // the internals goes with it even while developing — whoever failed to get in is
            // the last person to be shown the file names and the middleware chain.
            return $this->answer($exception->getStatusCode(), $exception->getMessage());
        } catch (HttpException $exception) {
            return $this->respond($exception->getStatusCode(), $exception->getMessage(), $exception);
        } catch (Throwable $throwable) {
            return $this->respond(StatusCode::INTERNAL_SERVER_ERROR, '', $throwable);
        }
    }

    /**
     * An answer which is not an error: the status and what it means, and nothing else,
     * wherever the application is running.
     */
    private function answer(int $status, string $message): ResponseInterface
    {
        return (new ErrorResponse($status))
            ->setError($message)
            ->withHeader('Content-Type', 'text/json');
    }

    /**
     * @param array<string, mixed> $details anything the client needs beyond the message
     */
    private function respond(
        int $status,
        string $message,
        Throwable $throwable,
        array $details = []
    ): ResponseInterface {
        if ($status >= StatusCode::INTERNAL_SERVER_ERROR) {
            $this->log($throwable);
        }

        $response = (new ErrorResponse($status))->setError($message)->addDetails($details);

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

        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);

        $logger->error($throwable->getMessage(), [
            'exception' => $throwable::class,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);
    }
}
