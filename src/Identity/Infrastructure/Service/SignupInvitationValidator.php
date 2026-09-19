<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Source\Account\Invitation\Domain\Exception\InvitationAlreadyUsedOrRevokedException;
use Source\Account\Invitation\Domain\Exception\InvitationExpiredException;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Domain\Exception\InvalidSignupInvitationException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;

readonly class SignupInvitationValidator implements SignupInvitationValidatorInterface
{
    public function __construct(private InvitationRepositoryInterface $invitationRepository)
    {
    }

    public function validate(OneTimeToken $token, Email $email): void
    {
        $invitation = $this->invitationRepository->findByToken($token);
        if ($invitation === null || (string) $invitation->email() !== (string) $email) {
            throw new InvalidSignupInvitationException('The signup invitation is invalid.');
        }

        try {
            $invitation->assertAcceptable();
        } catch (InvitationAlreadyUsedOrRevokedException|InvitationExpiredException $exception) {
            throw new InvalidSignupInvitationException('The signup invitation is invalid or expired.', previous: $exception);
        }
    }
}
