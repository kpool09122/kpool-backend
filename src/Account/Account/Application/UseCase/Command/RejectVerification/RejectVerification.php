<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectVerification;

use Source\Account\Account\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\Account\Domain\Repository\AccountVerificationRepositoryInterface;

readonly class RejectVerification implements RejectVerificationInterface
{
    public function __construct(
        private AccountVerificationRepositoryInterface $verificationRepository,
    ) {
    }

    /**
     * @param RejectVerificationInputPort $input
     * @param RejectVerificationOutputPort $output
     * @return void
     * @throws AccountVerificationNotFoundException
     */
    public function process(RejectVerificationInputPort $input, RejectVerificationOutputPort $output): void
    {
        // Find the verification
        $verification = $this->verificationRepository->findById($input->verificationIdentifier());

        if ($verification === null) {
            throw new AccountVerificationNotFoundException();
        }

        // Reject the verification with reason
        $verification->reject(
            $input->reviewerAccountIdentifier(),
            $input->rejectionReason(),
        );

        $this->verificationRepository->save($verification);

        $output->setVerification($verification);
    }
}
