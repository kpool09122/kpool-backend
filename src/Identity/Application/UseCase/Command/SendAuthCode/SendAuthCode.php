<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

use DateTimeImmutable;
use Source\Identity\Application\Service\AuthCodeSendingRateLimitServiceInterface;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCodeSession;

readonly class SendAuthCode implements SendAuthCodeInterface
{
    public function __construct(
        private AuthCodeServiceInterface $authCodeService,
        private IdentityRepositoryInterface $identityRepository,
        private AuthCodeSessionStorageServiceInterface $authCodeSessionStorageService,
        private AuthCodeSendingRateLimitServiceInterface $authCodeSendingRateLimitService,
    ) {
    }

    public function process(SendAuthCodeInputPort $input, SendAuthCodeOutputPort $output): void
    {
        $email = $input->email();
        $status = $this->authCodeSendingRateLimitService->reserve($email);
        $output->setStatus($status);
        if (! $status->sendingAllowed) {
            return;
        }

        $identity = $this->identityRepository->findByEmail($email);
        if ($identity !== null) {
            $this->authCodeService->notifyConflict($email, $input->language());

            return;
        }

        $code = $this->authCodeService->generateCode($email);
        $session = new AuthCodeSession($email, $code, new DateTimeImmutable('now'));
        $this->authCodeSessionStorageService->store($session);
        $this->authCodeService->send($email, $input->language(), $session);
    }
}
