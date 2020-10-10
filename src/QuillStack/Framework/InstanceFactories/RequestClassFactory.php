<?php

declare(strict_types=1);

namespace QuillStack\Framework\InstanceFactories;

use QuillStack\DI\Container;
use QuillStack\DI\CustomFactoryInterface;
use QuillStack\Http\Request\Factory\ServerRequest\GivenRequestFromGlobalsFactory;

final class RequestClassFactory implements CustomFactoryInterface
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * {@inheritDoc}
     */
    public function create(string $id)
    {
        $factory = $this->container->get(GivenRequestFromGlobalsFactory::class);

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
