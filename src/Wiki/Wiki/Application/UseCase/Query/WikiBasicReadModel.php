<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

use ArrayAccess;

/**
 * @extends ArrayAccess<string, mixed>
 */
interface WikiBasicReadModel extends ArrayAccess
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
