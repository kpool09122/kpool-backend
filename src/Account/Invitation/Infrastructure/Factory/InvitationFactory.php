<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class InvitationFactory implements InvitationFactoryInterface
{
    private const int EXPIRATION_HOURS = 24;

    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        AccountIdentifier $accountIdentifier,
        IdentityIdentifier $invitedByIdentityIdentifier,
        Email $email
    ): Invitation {
        $now = new DateTimeImmutable();

        return new Invitation(
            new InvitationIdentifier($this->uuidGenerator->generate()),
            $accountIdentifier,
            $invitedByIdentityIdentifier,
            $email,
            new OneTimeToken(bin2hex(random_bytes(32))),
            InvitationStatus::PENDING,
            $now->modify('+' . self::EXPIRATION_HOURS . ' hours'),
            null,
            null,
            $now,
        );
    }
}
