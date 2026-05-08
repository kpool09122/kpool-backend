<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

/**
 * @extends \ArrayAccess<string, mixed>
 */
interface WikiBasicReadModel extends \ArrayAccess
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
