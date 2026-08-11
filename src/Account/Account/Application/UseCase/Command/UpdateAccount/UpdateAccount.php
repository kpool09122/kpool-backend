<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Domain\ValueObject\ContactAddress;

readonly class UpdateAccount implements UpdateAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param UpdateAccountInputPort $input
     * @param UpdateAccountOutputPort $output
     * @return void
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function process(UpdateAccountInputPort $input, UpdateAccountOutputPort $output): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if (! $account) {
            throw new AccountNotFoundException();
        }

        if (
            (string) $input->principal()->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->policyEvaluator->evaluate(
                $input->principal(),
                Action::UPDATE,
                Resource::account($account->accountIdentifier(), $account->type()),
            )
        ) {
            throw new AccountUpdateForbiddenException();
        }

        $account->changeName($input->accountName());
        $account->changePhone($input->phone());
        $account->changeAddress(self::contactAddress($input));
        $this->accountRepository->save($account);

        $output->setAccount($account);
    }

    private static function contactAddress(UpdateAccountInputPort $input): ?ContactAddress
    {
        if (
            $input->addressCountryCode() === null
            && $input->addressAdministrativeAreaCode() === null
            && $input->addressPostalCode() === null
            && $input->addressLocality() === null
            && $input->addressLine1() === null
            && $input->addressLine2() === null
        ) {
            return null;
        }

        return ContactAddress::fromArray([
            'countryCode' => $input->addressCountryCode(),
            'administrativeAreaCode' => $input->addressAdministrativeAreaCode(),
            'postalCode' => $input->addressPostalCode(),
            'locality' => $input->addressLocality(),
            'addressLine1' => $input->addressLine1(),
            'addressLine2' => $input->addressLine2(),
        ]);
    }
}
