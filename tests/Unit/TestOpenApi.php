<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\Http\Responses\Exceptions\WrongTypeSerializedException;
use Quillstack\Framework\OpenApi\Generator;
use Quillstack\Framework\Tests\Fixtures\OpenApi\AdminPersonController;
use Quillstack\Framework\Tests\Fixtures\OpenApi\CreatePersonController;
use Quillstack\Framework\Tests\Fixtures\OpenApi\NoteController;
use Quillstack\Framework\Tests\Fixtures\OpenApi\PersonResponse;
use Quillstack\Framework\Tests\Fixtures\OpenApi\ShowPersonController;
use Quillstack\Framework\Tests\Fixtures\OpenApi\UndeclaredController;
use Quillstack\Framework\Tests\Fixtures\Person;
use Quillstack\Router\Router;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * The document is worked out from what the application already says, so what is tested here is
 * that it says the same thing the application does — and that where the application says
 * nothing, so does the document.
 */
class TestOpenApi
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $router = new Router();
        $router->get('/people/:id', ShowPersonController::class)->name('people.show');
        $router->post('/people', CreatePersonController::class)->name('people.create');
        $router->get('/people/:id/notes', ShowPersonController::class)
            ->name('people.notes')
            ->requireAuthentication('admin');

        return (new Generator($router, 'People', '2.0.0'))->generate();
    }

    public function theRoutesAreThePaths()
    {
        $document = $this->document();

        $this->assertEqual->equal('3.1.0', $document['openapi']);
        $this->assertEqual->equal('People', $document['info']['title']);
        $this->assertEqual->equal(['/people', '/people/{id}', '/people/{id}/notes'], array_keys($document['paths']));
        $this->assertEqual->equal('people.show', $document['paths']['/people/{id}']['get']['operationId']);
    }

    /**
     * `:id` is how a route says it and `{id}` is how OpenAPI does, and it is a parameter in
     * both.
     */
    public function aPathParameterIsDescribedAsOne()
    {
        $parameters = $this->document()['paths']['/people/{id}']['get']['parameters'];

        $this->assertEqual->equal(1, count($parameters));
        $this->assertEqual->equal('id', $parameters[0]['name']);
        $this->assertEqual->equal('path', $parameters[0]['in']);
        $this->assertEqual->equal(true, $parameters[0]['required']);
    }

    public function aRouteWhichNeedsATokenSaysSo()
    {
        $document = $this->document();
        $guarded = $document['paths']['/people/{id}/notes']['get'];

        $this->assertEqual->equal([['bearerAuth' => ['admin']]], $guarded['security']);
        $this->assertEqual->equal('Not authenticated', $guarded['responses'][401]['description']);
        $this->assertEqual->equal('Not authorised', $guarded['responses'][403]['description']);
        $this->assertEqual->equal('bearer', $document['components']['securitySchemes']['bearerAuth']['scheme']);
    }

    /**
     * A route nobody guarded has nothing to say about tokens, and says nothing.
     */
    public function aRouteWhichDoesNotNeedOneIsSilentAboutIt()
    {
        $open = $this->document()['paths']['/people/{id}']['get'];

        // An empty list is how OpenAPI says an endpoint needs nothing. Leaving the key out
        // says nothing at all, which is a different statement.
        $this->assertEqual->equal([], $open['security']);
        $this->assertBoolean->isFalse(isset($open['responses'][401]));
    }

    /**
     * The rules the validator reads are the rules the document describes. There is one list.
     */
    public function whatARequestMayHoldIsTheRulesThatRun()
    {
        $schema = $this->document()['paths']['/people']['post']['requestBody']['content']['application/json']['schema'];

        $this->assertEqual->equal(['email', 'age', 'plan', 'name'], array_keys($schema['properties']));
        $this->assertEqual->equal('email', $schema['properties']['email']['format']);
        $this->assertEqual->equal(['free', 'pro'], $schema['properties']['plan']['enum']);

        // `name` has no `required` rule, so it is not required here either.
        $this->assertEqual->equal(['email', 'age', 'plan'], $schema['required']);
    }

    /**
     * `min:18` on a number is a minimum and `max:60` on a string is a length. Reading either
     * as the other would describe an API nobody wrote.
     */
    public function aBoundMeansWhatTheTypeSaysItMeans()
    {
        $properties = $this->document()['paths']['/people']['post']['requestBody']['content']['application/json']['schema']['properties'];

        $this->assertEqual->equal(18, $properties['age']['minimum']);
        $this->assertBoolean->isFalse(isset($properties['age']['minLength']));

        $this->assertEqual->equal(60, $properties['name']['maxLength']);
        $this->assertBoolean->isFalse(isset($properties['name']['maximum']));
    }

    public function anEndpointWhichValidatesCanAnswer422()
    {
        $document = $this->document();

        $this->assertEqual->equal('The given data was invalid', $document['paths']['/people']['post']['responses'][422]['description']);
        $this->assertBoolean->isFalse(isset($document['paths']['/people/{id}']['get']['responses'][422]));
    }

    /**
     * The fields are the ones the serializer would send, because they are read from where it
     * reads them. `password` is on the entity and exposed to nobody, and the document cannot
     * mention it for the same reason a response cannot carry it.
     */
    public function theSchemaIsWhatTheEntityExposesAndNothingElse()
    {
        $schema = $this->document()['components']['schemas']['Person'];

        // `note` is for administrators, and this response does not serve them.
        $this->assertEqual->equal(['id', 'name', 'email_address'], array_keys($schema['properties']));
        $this->assertBoolean->isFalse(isset($schema['properties']['password']));
        $this->assertBoolean->isFalse(isset($schema['properties']['note']));
        // Nullable in 3.1 is a list of types, not a  flag — that was 3.0.
        $this->assertEqual->equal('integer', $schema['properties']['id']['type']);
        $this->assertEqual->equal('string', $schema['properties']['email_address']['type']);
    }

    /**
     * The same entity read for a different audience is a different set of fields, so it is a
     * different schema. One name over both would describe neither.
     */
    public function anotherAudienceIsAnotherSchema()
    {
        $router = new Router();
        $router->get('/people/:id', ShowPersonController::class)->name('people.show');
        $router->get('/admin/people/:id', AdminPersonController::class)->name('admin.people.show');

        $schemas = (new Generator($router))->generate()['components']['schemas'];

        $this->assertEqual->equal(['Person', 'PersonAdmin'], array_keys($schemas));
        $this->assertEqual->equal(['id', 'name', 'email_address'], array_keys($schemas['Person']['properties']));
        $this->assertEqual->equal(['id', 'name', 'email_address', 'note'], array_keys($schemas['PersonAdmin']['properties']));
    }

    /**
     * OpenAPI 3.1 says a nullable type by listing null among the types. `nullable: true` is
     * how 3.0 said it, and a document declaring 3.1 while carrying it is not a valid one —
     * which a validator says and a reader does not.
     */
    public function aFieldWhichMayBeNullIsWrittenTheWayThreeOneWritesIt()
    {
        $router = new Router();
        $router->get('/notes/:id', NoteController::class)->name('notes.show');

        $schema = (new Generator($router))->generate()['components']['schemas']['Note'];

        $this->assertEqual->equal(['string', 'null'], $schema['properties']['title']['type']);
        $this->assertEqual->equal('integer', $schema['properties']['id']['type']);

        // Nullable means not required, and both are said in their own way.
        $this->assertEqual->equal(['id', 'body'], $schema['required']);
    }

    public function theResponsePointsAtTheSchema()
    {
        $content = $this->document()['paths']['/people/{id}']['get']['responses'][200]['content'];

        $this->assertEqual->equal('#/components/schemas/Person', $content['application/json']['schema']['$ref']);
    }

    /**
     * Where nothing is declared, nothing is invented. A document which is partly guessed is
     * worse than a short one, because there is no way to tell which half is which.
     */
    public function whatIsNotDeclaredIsLeftOutRatherThanGuessed()
    {
        $router = new Router();
        $router->get('/undeclared', UndeclaredController::class)->name('undeclared');

        $document = (new Generator($router))->generate();
        $response = $document['paths']['/undeclared']['get']['responses'][200];

        $this->assertEqual->equal('OK', $response['description']);
        $this->assertBoolean->isFalse(isset($response['content']));
        $this->assertBoolean->isFalse(isset($document['components']['schemas']));
    }

    /**
     * The declaration is held to, which is what stops it drifting from the code. An attribute
     * that only documents is wrong the first time somebody changes one and not the other, and
     * then it is worse than nothing, because it is believed.
     */
    public function aResponseIsHeldToWhatItSaysItCarries()
    {
        $response = new PersonResponse();
        $response->with(new Person(1, 'Ada'));

        $this->assertEqual->equal(['id' => 1, 'name' => 'Ada', 'email_address' => ''], $response->send());

        $this->assertExceptions->expect(WrongTypeSerializedException::class);

        $response->with(new \stdClass());
    }

    /**
     * Where the API answers is the one thing in the document that the code cannot know, so it
     * is the one thing that is passed in.
     */
    public function whereItAnswersIsToldRatherThanFoundOut()
    {
        $router = new Router();
        $router->get('/people/:id', ShowPersonController::class)->name('people.show');

        $document = (new Generator($router, 'People', '1.0.0', ['https://api.example.com']))->generate();

        $this->assertEqual->equal([['url' => 'https://api.example.com']], $document['servers']);

        $withoutServers = (new Generator($router))->generate();

        $this->assertBoolean->isFalse(isset($withoutServers['servers']));
    }

    /**
     * The sentence above `handle()` is one somebody already wrote next to the code, so it is
     * not a second thing to keep in step.
     */
    public function theSummaryIsTheSentenceAboveTheCode()
    {
        $summary = $this->document()['paths']['/people']['post']['summary'];

        $this->assertEqual->equal('Takes a person and keeps them.', $summary);
    }

    public function theDocumentIsJson()
    {
        $json = json_encode($this->document());

        $this->assertBoolean->isTrue(is_string($json));
        $this->assertEqual->equal('3.1.0', json_decode((string) $json, true)['openapi']);
    }
}
