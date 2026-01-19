<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Infrastructure\Service;

use Application\Mail\InvitationMail;
use Illuminate\Support\Facades\Mail;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\Language;

readonly class InvitationMailService implements InvitationMailServiceInterface
{
    private const array FALLBACK_ACCOUNT_NAMES = [
        'ja' => 'アカウント',
        'en' => 'Account',
        'ko' => '계정',
    ];

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private IdentityRepositoryInterface $identityRepository,
        private string $frontendBaseUrl,
    ) {
    }

    public function sendInvitationEmail(Invitation $invitation): void
    {
        $identity = $this->identityRepository->findById($invitation->invitedByIdentityIdentifier());
        $language = $identity?->language() ?? Language::ENGLISH;

        $account = $this->accountRepository->findById($invitation->accountIdentifier());
        $accountName = $account !== null
            ? (string) $account->name()
            : self::FALLBACK_ACCOUNT_NAMES[$language->value];

        $invitationUrl = $this->frontendBaseUrl . '/signup?token=' . $invitation->token();

        Mail::to((string) $invitation->email())->send(
            new InvitationMail($invitation, $invitationUrl, $accountName, $language)
        );
    }
}
