<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Query;

use Application\Models\Monetization\PaymentMethod as PaymentMethodModel;
use Application\Models\Monetization\PaymentMethodArchive as ArchiveModel;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Account\Application\UseCase\Query\ArchivedPaymentMethodReadModel;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInputPort;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodDetailsReadModel;
use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodReadModel;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

readonly class GetPaymentMethod implements GetPaymentMethodInterface
{
    public function __construct(
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalReachabilityInterface $principalReachability,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(GetPaymentMethodInputPort $input): PaymentMethodDetailsReadModel
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
        $current = PaymentMethodModel::query()->where('id', (string) $input->paymentMethodIdentifier())
            ->where('monetization_account_id', (string) $account->monetizationAccountIdentifier())->first();
        if ($current !== null) {
            return new PaymentMethodReadModel($current->id, $current->type, $current->brand, $current->last4, $current->exp_month, $current->exp_year, $current->is_default, $current->status);
        }
        $archive = ArchiveModel::query()->where('payment_method_id', (string) $input->paymentMethodIdentifier())
            ->where('monetization_account_id', (string) $account->monetizationAccountIdentifier())->first();
        if ($archive === null) {
            throw new PaymentMethodNotFoundException();
        }

        return new ArchivedPaymentMethodReadModel($archive->payment_method_id, $archive->type, $archive->brand, $archive->last4, $archive->archived_at->format(DATE_ATOM));
    }
}
