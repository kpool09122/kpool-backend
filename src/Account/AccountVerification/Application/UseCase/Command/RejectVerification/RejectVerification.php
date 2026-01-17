<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;

readonly class RejectVerification implements RejectVerificationInterface
{
    public function __construct(
        private AccountVerificationRepositoryInterface $verificationRepository,
    ) {
    }

    /**
     * @param RejectVerificationInputPort $input
     * @return AccountVerification
     * @throws AccountVerificationNotFoundException
     */
    public function process(RejectVerificationInputPort $input): AccountVerification
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

        return $verification;
    }
}
