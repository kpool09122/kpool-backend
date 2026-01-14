<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;

readonly class ApproveVerification implements ApproveVerificationInterface
{
    public function __construct(
        private AccountVerificationRepositoryInterface $verificationRepository,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    /**
     * @param ApproveVerificationInputPort $input
     * @return AccountVerification
     * @throws AccountVerificationNotFoundException
     */
    public function process(ApproveVerificationInputPort $input): AccountVerification
    {
        // Find the verification
        $verification = $this->verificationRepository->findById($input->verificationIdentifier());

        if ($verification === null) {
            throw new AccountVerificationNotFoundException();
        }

        // Approve the verification
        $verification->approve($input->reviewerAccountIdentifier());

        // Update the account category
        $account = $this->accountRepository->findById($verification->accountIdentifier());

        if ($account !== null) {
            $newCategory = $verification->verificationType()->toAccountCategory();
            $account->setAccountCategory($newCategory);
            $this->accountRepository->save($account);
        }

        $this->verificationRepository->save($verification);

        return $verification;
    }
}
