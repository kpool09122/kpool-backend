<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateIdentity;

use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class CreateIdentityInput implements CreateIdentityInputPort
{
    public function __construct(
        private UserName         $username,
        private Email            $email,
        private Language         $language,
        private PlainPassword    $password,
        private PlainPassword    $confirmedPassword,
        private ?string          $base64EncodedImage,
        private ?InvitationToken $invitationToken = null,
    ) {
    }

    public function username(): UserName
    {
        return $this->username;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function password(): PlainPassword
    {
        return $this->password;
    }

    public function confirmedPassword(): PlainPassword
    {
        return $this->confirmedPassword;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }

    public function invitationToken(): ?InvitationToken
    {
        return $this->invitationToken;
    }
}
