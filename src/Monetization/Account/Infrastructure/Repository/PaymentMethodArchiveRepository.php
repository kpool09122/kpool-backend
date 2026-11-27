<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Repository;

use Application\Models\Monetization\PaymentMethodArchive as ArchiveModel;
use DateTimeImmutable;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\Repository\PaymentMethodArchiveRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

class PaymentMethodArchiveRepository implements PaymentMethodArchiveRepositoryInterface
{
    public function save(PaymentMethodArchive $archive): void
    {
        ArchiveModel::query()->create([
            'id' => (string) $archive->identifier,
            'payment_method_id' => (string) $archive->paymentMethodIdentifier,
            'monetization_account_id' => (string) $archive->monetizationAccountIdentifier,
            'external_payment_method_id' => (string) $archive->externalPaymentMethodId,
            'type' => $archive->type->value,
            'brand' => $archive->brand,
            'last4' => $archive->last4,
            'archived_at' => $archive->archivedAt,
        ]);
    }

    public function findByPaymentMethodIdentifier(PaymentMethodIdentifier $identifier): ?PaymentMethodArchive
    {
        $row = ArchiveModel::query()->where('payment_method_id', (string) $identifier)->first();
        if ($row === null) {
            return null;
        }

        return new PaymentMethodArchive(
            new PaymentMethodArchiveIdentifier($row->id),
            new PaymentMethodIdentifier($row->payment_method_id),
            new MonetizationAccountIdentifier($row->monetization_account_id),
            new ExternalPaymentMethodId($row->external_payment_method_id),
            PaymentMethodType::from($row->type),
            $row->brand,
            $row->last4,
            DateTimeImmutable::createFromInterface($row->archived_at),
        );
    }
}
