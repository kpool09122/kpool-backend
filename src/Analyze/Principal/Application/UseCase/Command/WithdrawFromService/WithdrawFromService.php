<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService;

use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class WithdrawFromService implements WithdrawFromServiceInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void
    {
        foreach ($this->principalRepository->findAllByIdentityIdentifier($input->identityIdentifier()) as $principal) {
            $this->principalRepository->delete($principal);
        }
    }
}
