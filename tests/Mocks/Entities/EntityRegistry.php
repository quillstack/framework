<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Entities;

use Quillstack\Framework\Database\EntityRegistryInterface;

final class EntityRegistry implements EntityRegistryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getEntities(): array
    {
        return [Note::class];
    }
}
