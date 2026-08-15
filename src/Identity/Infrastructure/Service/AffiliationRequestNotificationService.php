<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\AffiliationRequestMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\Email;

readonly class AffiliationRequestNotificationService implements AffiliationRequestNotificationServiceInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private string $frontendBaseUrl,
    ) {
    }

    public function sendAffiliationRequestNotification(Email $targetEmail): void
    {
        $identity = $this->identityRepository->findByEmail($targetEmail);
        if ($identity === null) {
            return;
        }

        $send = fn (): mixed => Mail::to((string) $targetEmail)->send(new AffiliationRequestMail(
            $this->buildAffiliationUrl(),
            $identity->language(),
        ));

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($send);

            return;
        }

        $send();
    }

    private function buildAffiliationUrl(): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/admin/account/affiliations';
    }
}
