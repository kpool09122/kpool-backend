<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;

readonly class DeletionReadinessChecklist
{
    /** @var DeletionBlockReason[] */
    private array $blockers;

    /**
     * @param DeletionBlockReason[] $blockers
     */
    public function __construct(array $blockers = [])
    {
        $this->blockers = array_values(
            array_reduce(
                $blockers,
                static function (array $carry, DeletionBlockReason $reason): array {
                    $carry[$reason->value] = $reason;

                    return $carry;
                },
                []
            )
        );
    }

    public static function ready(): self
    {
        return new self();
    }

    public static function fromReasons(DeletionBlockReason ...$blockers): self
    {
        return new self($blockers);
    }

    /**
     * @return DeletionBlockReason[]
     */
    public function blockers(): array
    {
        return $this->blockers;
    }

    public function isReady(): bool
    {
        return $this->blockers() === [];
    }

    /**
     * @throws AccountDeletionBlockedException
     */
    public function assertReady(): void
    {
        $blockers = $this->blockers();

        if ($blockers !== []) {
            throw new AccountDeletionBlockedException($blockers);
        }
    }
}
