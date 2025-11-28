<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Factory;

use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface UserFactoryInterface
{
    public function create(
        UserName      $username,
        Email         $email,
        Language      $language,
        PlainPassword $plainPassword
    ): User;
}
