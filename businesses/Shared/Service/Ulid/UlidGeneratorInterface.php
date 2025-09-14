<?php

namespace Businesses\Shared\Service\Ulid;

interface UlidGeneratorInterface
{
    public function generate(): string;
}
