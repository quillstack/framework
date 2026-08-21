<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\Validation\Exceptions\UnknownRuleException;
use Quillstack\Framework\Validation\Exceptions\ValidationFailedException;
use Quillstack\Framework\Validation\RuleFactory;
use Quillstack\Framework\Validation\Validator;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertArray;

class TestValidation
{
    private Validator $validator;

    public function __construct(
        private AssertEqual $assertEqual,
        private AssertArray $assertArray,
        private AssertExceptions $assertExceptions
    ) {
        $this->validator = new Validator(new RuleFactory());
    }

    public function everythingValidComesBack()
    {
        $data = $this->validator->check(
            ['email' => 'radek@quillstack.com', 'age' => '30', 'extra' => 'ignored'],
            ['email' => ['required', 'email'], 'age' => ['required', 'integer', 'min:18']]
        );

        // Only the fields there were rules for reach the application.
        $this->assertEqual->equal(['email' => 'radek@quillstack.com', 'age' => '30'], $data);
    }

    public function everyBrokenRuleIsReportedAtOnce()
    {
        $errors = $this->validator->findErrors(
            ['email' => 'not-an-email', 'age' => 'x'],
            ['email' => ['required', 'email'], 'age' => ['required', 'integer', 'min:18'], 'plan' => ['required']]
        );

        $this->assertArray->hasKey('email', $errors);
        $this->assertArray->hasKey('age', $errors);
        $this->assertArray->hasKey('plan', $errors);
        $this->assertEqual->equal('The plan field is required', $errors['plan'][0]);
        $this->assertEqual->equal('The email field has to be an email address', $errors['email'][0]);

        // Both integer and min fail on 'x', and the client is told about both.
        $this->assertEqual->equal(2, count($errors['age']));
    }

    public function aMissingFieldIsOnlyRequiredsBusiness()
    {
        $errors = $this->validator->findErrors([], ['email' => ['email'], 'age' => ['integer']]);

        $this->assertEqual->equal([], $errors);
    }

    public function anEmptyStringDoesNotCountAsSent()
    {
        $errors = $this->validator->findErrors(['name' => '  '], ['name' => ['required']]);

        $this->assertEqual->equal('The name field is required', $errors['name'][0]);
    }

    public function sizeReadsALengthForStringsAndAValueForNumbers()
    {
        $this->assertEqual->equal([], $this->validator->findErrors(['name' => 'Radek'], ['name' => ['min:3', 'max:10']]));
        $this->assertArray->hasKey('name', $this->validator->findErrors(['name' => 'Ra'], ['name' => ['min:3']]));
        $this->assertEqual->equal([], $this->validator->findErrors(['age' => 30], ['age' => ['min:18', 'max:120']]));
        $this->assertArray->hasKey('age', $this->validator->findErrors(['age' => 17], ['age' => ['min:18']]));
    }

    public function oneFieldCanBeCheckedAgainstAnother()
    {
        $rules = ['repeat' => ['same:password']];

        $this->assertEqual->equal([], $this->validator->findErrors(['password' => 'a', 'repeat' => 'a'], $rules));
        $this->assertEqual->equal(
            'The repeat field has to match password',
            $this->validator->findErrors(['password' => 'a', 'repeat' => 'b'], $rules)['repeat'][0]
        );
    }

    public function checkThrowsWithEverythingThatWasWrong()
    {
        try {
            $this->validator->check(['age' => 'x'], ['email' => ['required'], 'age' => ['integer']]);
        } catch (ValidationFailedException $exception) {
            $this->assertEqual->equal(422, $exception->getStatusCode());
            $this->assertEqual->equal('The given data was invalid', $exception->getMessage());
            $this->assertArray->hasKey('email', $exception->getErrors());
            $this->assertArray->hasKey('age', $exception->getErrors());

            return;
        }

        $this->assertEqual->equal('an exception', 'nothing thrown');
    }

    public function aRuleNobodyKnowsIsReported()
    {
        $this->assertExceptions->expect(UnknownRuleException::class);

        $this->validator->findErrors(['a' => 1], ['a' => ['nonsense']]);
    }
}
