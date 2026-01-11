<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Source\Account\Account\Domain\ValueObject\DeletionBlockReason;
use Throwable;

class AccountDeletionBlockedException extends DomainException
{
    /**
     * @param DeletionBlockReason[] $blockers
     */
    public function __construct(
        private readonly array $blockers,
        ?Throwable $previous = null,
    ) {
        $reasonText = implode(
            ', ',
            array_map(
                static fn (DeletionBlockReason $reason) => $reason->value,
                $blockers
            )
        );

        parent::__construct("Account deletion is blocked: {$reasonText}", 0, $previous);
    }

    /**
     * @return DeletionBlockReason[]
     */
    public function blockers(): array
    {
        return $this->blockers;
    }
}
