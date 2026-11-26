<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Query;

use Application\Models\Monetization\PaymentMethod as PaymentMethodModel;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInputPort;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInterface;
use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodReadModel;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

readonly class ListPaymentMethods implements ListPaymentMethodsInterface
{
    public function __construct(
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalReachabilityInterface $principalReachability,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(ListPaymentMethodsInputPort $input): array
    {
        $account = $this->monetizationAccountRepository->findByAccountIdentifier($input->accountIdentifier());
        if ($account === null) {
            throw new UnauthorizedException();
        }
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($principal === null
            || (string) $principal->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->principalReachability->canAccess($principal->identityIdentifier(), $account->accountIdentifier())
            || ! $this->policyEvaluator->evaluate($principal, Action::PAYMENT_METHOD_READ, new Resource(ResourceType::PAYMENT_METHOD, $account->accountIdentifier()))) {
            throw new UnauthorizedException();
        }

        return array_values(PaymentMethodModel::query()->where('monetization_account_id', (string) $account->monetizationAccountIdentifier())
            ->orderBy('id')->get()->map(static fn (PaymentMethodModel $row): PaymentMethodReadModel => new PaymentMethodReadModel(
                $row->id,
                $row->type,
                $row->brand,
                $row->last4,
                $row->exp_month,
                $row->exp_year,
                $row->is_default,
                $row->status,
            ))->all());
    }
}
