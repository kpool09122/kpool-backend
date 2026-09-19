<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use Source\Identity\Domain\Entity\PasskeyUser;

interface PasskeyUserFactoryInterface
{
    public function create(): PasskeyUser;
}
