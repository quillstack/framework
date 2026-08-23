<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Psr\Http\Message\ResponseInterface;
use Quillstack\Auth\IdentityProviderInterface;
use Quillstack\Framework\App;
use Quillstack\Framework\Exceptions\NoIdentityProviderException;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Auth\Users;
use Quillstack\Framework\Tests\Mocks\Providers\GuardedRouteProvider;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestAuthentication
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
    ) {
        //
    }

    /**
     * @param array<string, mixed> $config
     */
    private function ask(string $path, ?string $token = null, array $config = []): ResponseInterface
    {
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => $path,
            'SERVER_PROTOCOL' => '1.1',
            'APP_ENV' => 'production',
        ];

        if ($token !== null) {
            $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";
        }

        return (new App('', $config + [
            RouteProviderInterface::class => GuardedRouteProvider::class,
            IdentityProviderInterface::class => Users::class,
        ]))->run();
    }

    public function anOpenRouteAnswersWithoutAToken()
    {
        $this->assertEqual->equal(200, $this->ask('/version')->getStatusCode());
    }

    /**
     * 401 says try again with credentials.
     */
    public function aGuardedRouteWithoutOneIsRefused()
    {
        $response = $this->ask('/private');

        $this->assertEqual->equal(401, $response->getStatusCode());
        $this->assertEqual->equal(
            '{"error":{"status":401,"message":"Not authenticated"}}',
            json_encode($response)
        );
    }

    public function aGuardedRouteWithOneAnswers()
    {
        $this->assertEqual->equal(200, $this->ask('/private', Users::TOKEN)->getStatusCode());
    }

    /**
     * A token standing for nobody is the same answer as no token at all.
     */
    public function aTokenNobodyKnowsIsRefusedTheSameWay()
    {
        $this->assertEqual->equal(401, $this->ask('/private', 'nonsense')->getStatusCode());
    }

    /**
     * 403 says do not bother: recognised, and not allowed.
     */
    public function recognisedWithoutTheRoleIsRefusedDifferently()
    {
        $response = $this->ask('/admin', Users::TOKEN);

        $this->assertEqual->equal(403, $response->getStatusCode());
        $this->assertEqual->equal(
            '{"error":{"status":403,"message":"Not allowed"}}',
            json_encode($response)
        );
    }

    /**
     * A guarded route in an application which has not said who anybody is would be open while
     * reading as guarded. That is refused at boot, before a single request is served.
     */
    public function aGuardNobodyEnforcesIsRefusedAtBoot()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $this->assertExceptions->expect(NoIdentityProviderException::class);

        (new App('', [
            RouteProviderInterface::class => GuardedRouteProvider::class,
        ]))->run();
    }

    /**
     * And an application with no guarded routes needs nothing of the sort.
     */
    public function anApplicationWithoutGuardsNeedsNoProvider()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $response = (new App('', [
            RouteProviderInterface::class => \Quillstack\Framework\Tests\Mocks\Providers\RouteProvider::class,
        ]))->run();

        $this->assertEqual->equal(200, $response->getStatusCode());
    }

    /**
     * Nothing matched is still nothing found — a 404 turned into a 401 would say the page
     * exists.
     */
    public function nothingMatchedIsStillNotFound()
    {
        $this->assertEqual->equal(404, $this->ask('/nowhere')->getStatusCode());
    }

    /**
     * A refusal says the status and what it means, and nothing else — not even while
     * developing. Whoever failed to get in is the last person to be shown the file names and
     * the middleware chain.
     */
    public function aRefusalNeverDescribesTheInternals()
    {
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'develop';
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/private',
            'SERVER_PROTOCOL' => '1.1',
            'APP_ENV' => 'develop',
        ];

        $response = (new App('', [
            RouteProviderInterface::class => GuardedRouteProvider::class,
            IdentityProviderInterface::class => Users::class,
        ]))->run();

        $body = (string) json_encode($response);

        $this->assertEqual->equal(401, $response->getStatusCode());
        $this->assertEqual->equal('{"error":{"status":401,"message":"Not authenticated"}}', $body);
        $this->assertBoolean->isFalse(str_contains($body, 'trace'));
        $this->assertBoolean->isFalse(str_contains($body, 'Middleware.php'));
    }
}
