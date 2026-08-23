<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\Http\Responses\SerializedResponse;
use Quillstack\Framework\Tests\Fixtures\Person;
use Quillstack\UnitTests\AssertEqual;

final class PeopleResponse extends SerializedResponse
{
}

final class AdminResponse extends SerializedResponse
{
    /**
     * {@inheritDoc}
     */
    protected function groups(): array
    {
        return ['admin'];
    }
}

class TestSerializedResponse
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    private function person(): Person
    {
        return new Person(1, 'Ada', 'ada@example.com', 'pays late', 'secret');
    }

    public function whatTheObjectSaysMayGo()
    {
        $response = (new PeopleResponse())->with($this->person());

        $this->assertEqual->equal([
            'id' => 1,
            'name' => 'Ada',
            'email_address' => 'ada@example.com',
        ], $response->send());
    }

    /**
     * The field nobody exposed is not there, and will not be there the day a column is added
     * beside it either.
     */
    public function andNothingElse()
    {
        $sent = (new PeopleResponse())->with($this->person())->send();

        $this->assertEqual->equal(false, array_key_exists('password', $sent));
        $this->assertEqual->equal(false, array_key_exists('note', $sent));
    }

    public function aResponseCanSayWhoItIsFor()
    {
        $sent = (new AdminResponse())->with($this->person())->send();

        $this->assertEqual->equal('pays late', $sent['note']);
        $this->assertEqual->equal(false, array_key_exists('password', $sent));
    }

    public function aListOfThem()
    {
        $sent = (new PeopleResponse())->with([
            new Person(1, 'Ada'),
            new Person(2, 'Grace'),
        ])->send();

        $this->assertEqual->equal(2, count($sent));
        $this->assertEqual->equal('Grace', $sent[1]['name']);
    }

    /**
     * A response nobody gave anything to carries nothing, rather than falling over.
     */
    public function nothingToSay()
    {
        $this->assertEqual->equal([], (new PeopleResponse())->send());
    }
}
