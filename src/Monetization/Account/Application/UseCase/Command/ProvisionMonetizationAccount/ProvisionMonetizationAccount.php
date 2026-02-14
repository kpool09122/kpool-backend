<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Monetization\Account\Application\Exception\MonetizationAccountAlreadyExistsException;
use Source\Monetization\Account\Domain\Factory\MonetizationAccountFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;

readonly class ProvisionMonetizationAccount implements ProvisionMonetizationAccountInterface
{
    public function __construct(
        private MonetizationAccountRepositoryInterface $repository,
        private MonetizationAccountFactoryInterface $factory,
    ) {
    }

    /**
     * @param ProvisionMonetizationAccountInputPort $input
     * @param ProvisionMonetizationAccountOutputPort $output
     * @return void
     * @throws MonetizationAccountAlreadyExistsException
     */
    public function process(ProvisionMonetizationAccountInputPort $input, ProvisionMonetizationAccountOutputPort $output): void
    {
        $existing = $this->repository->findByAccountIdentifier($input->accountIdentifier());

        if ($existing !== null) {
            throw new MonetizationAccountAlreadyExistsException();
        }

        $account = $this->factory->create($input->accountIdentifier());
        $this->repository->save($account);

        $output->setMonetizationAccount($account);
    }
}
