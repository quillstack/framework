<?php

declare(strict_types=1);

namespace Quillstack\Framework\Database;

/**
 * The entities of the application. The schema is worked out from them, so this is the one
 * place saying which classes the database is expected to hold.
 */
interface EntityRegistryInterface
{
    /**
     * @return array<int, class-string>
     */
    public function getEntities(): array;
}
