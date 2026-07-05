<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface IdentityFactoryInterface
{
    public function create(
        IdentityName      $identityName,
        Email         $email,
        Language      $language,
        PlainPassword $plainPassword
    ): Identity;

    public function createFromSocialProfile(SocialProfile $profile): Identity;

    public function createDelegatedIdentity(
        Identity $originalIdentity,
        DelegationIdentifier $delegationIdentifier,
    ): Identity;
}
