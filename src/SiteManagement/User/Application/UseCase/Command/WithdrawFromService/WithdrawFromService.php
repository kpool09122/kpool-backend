<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\WithdrawFromService;

use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class WithdrawFromService implements WithdrawFromServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void
    {
        $this->userRepository->deleteByIdentityIdentifier($input->identityIdentifier());
    }
}
