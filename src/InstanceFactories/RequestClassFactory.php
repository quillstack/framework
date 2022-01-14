<?php

declare(strict_types=1);

namespace Quillstack\Framework\InstanceFactories;

use Quillstack\DI\Container;
use Quillstack\DI\CustomFactoryInterface;
use Quillstack\ServerRequest\Factory\ServerRequest\GivenServerRequestFromGlobalsFactory;

class RequestClassFactory implements CustomFactoryInterface
{
    private Container $container;

    public function create(string $id): self
    {
        $factory = $this->container->get(GivenServerRequestFromGlobalsFactory::class);

        return $factory->createGivenServerRequest($id);
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }
}
