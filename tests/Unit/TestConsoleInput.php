<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\Console\Input;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;
use Quillstack\UnitTests\Types\AssertNull;

class TestConsoleInput
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertNull $assertNull
    ) {
        //
    }

    public function theCommandComesFirst()
    {
        $input = Input::fromArgv(['quill', 'cache:clear']);

        $this->assertEqual->equal('cache:clear', $input->getCommand());
        $this->assertEqual->equal([], $input->getArguments());
    }

    public function nothingTypedMeansList()
    {
        $this->assertEqual->equal('list', Input::fromArgv(['quill'])->getCommand());
    }

    public function argumentsKeepTheirOrder()
    {
        $input = Input::fromArgv(['quill', 'copy', 'from.txt', 'to.txt']);

        $this->assertEqual->equal(['from.txt', 'to.txt'], $input->getArguments());
        $this->assertEqual->equal('from.txt', $input->getArgument(0));
        $this->assertEqual->equal('to.txt', $input->getArgument(1));
        $this->assertNull->isNull($input->getArgument(2));
        $this->assertEqual->equal('none', $input->getArgument(2, 'none'));
    }

    public function optionsAreReadInEveryForm()
    {
        $input = Input::fromArgv(['quill', 'build', '--target=prod', '--force', '-vq']);

        $this->assertEqual->equal('prod', $input->getOption('target'));
        $this->assertBoolean->isTrue($input->getOption('force'));
        $this->assertBoolean->isTrue($input->getOption('v'));
        $this->assertBoolean->isTrue($input->getOption('q'));
        $this->assertBoolean->isTrue($input->hasOption('force'));
        $this->assertBoolean->isFalse($input->hasOption('nothing'));
        $this->assertEqual->equal('fallback', $input->getOption('nothing', 'fallback'));
    }

    public function optionsMayComeBeforeTheCommand()
    {
        $input = Input::fromArgv(['quill', '--verbose', 'greet', 'Radek']);

        $this->assertEqual->equal('greet', $input->getCommand());
        $this->assertEqual->equal(['Radek'], $input->getArguments());
        $this->assertBoolean->isTrue($input->hasOption('verbose'));
    }
}
