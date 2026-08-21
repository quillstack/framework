<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertArray;

/**
 * Validation is an HTTP exception, so nothing between the controller and the client has to
 * know about it: the error middleware answers 422 and lists the fields.
 */
class TestValidationOverHttp
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertArray $assertArray
    ) {
        //
    }

    /**
     * @param array<string, mixed> $post
     */
    private function signUp(array $post)
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/sign-up',
            'SERVER_PROTOCOL' => '1.1',
        ];
        $_POST = $post;
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]))->run();
    }

    public function validInputReachesTheController()
    {
        $response = $this->signUp(['email' => 'radek@quillstack.com', 'age' => '30', 'plan' => 'pro']);

        $this->assertEqual->equal(200, $response->getStatusCode());
        $this->assertEqual->equal('radek@quillstack.com', $response->send()['user']);
    }

    public function brokenInputIsAnsweredWith422()
    {
        $response = $this->signUp(['email' => 'nope', 'age' => '12', 'plan' => 'enterprise']);
        $body = $response->send();

        $this->assertEqual->equal(422, $response->getStatusCode());
        $this->assertEqual->equal('The given data was invalid', $body['error']['message']);

        $fields = $body['error']['fields'];
        $this->assertArray->hasKey('email', $fields);
        $this->assertArray->hasKey('age', $fields);
        $this->assertArray->hasKey('plan', $fields);
        $this->assertEqual->equal('The age field has to be at least 18', $fields['age'][0]);
        $this->assertEqual->equal('The plan field has to be one of: free, pro', $fields['plan'][0]);
    }

    public function nothingSentAtAllIsAnsweredWith422()
    {
        $response = $this->signUp([]);

        $this->assertEqual->equal(422, $response->getStatusCode());
        $this->assertEqual->equal(3, count($response->send()['error']['fields']));
    }
}
