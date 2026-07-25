<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateIdentity;

use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\OneTimeToken;

interface CreateIdentityInputPort
{
    public function identityName(): IdentityName;

    public function email(): Email;

    public function language(): Language;

    public function password(): PlainPassword;

    public function confirmedPassword(): PlainPassword;

    public function base64EncodedImage(): ?string;

    public function oneTimeToken(): ?OneTimeToken;
}
