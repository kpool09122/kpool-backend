<?php

declare(strict_types=1);

namespace Source\Account\Policy\Domain\ValueObject;

final readonly class Statement
{
    /**
     * @param AccountAction[] $actions
     * @param AccountResourceType[] $resourceTypes
     */
    public function __construct(
        private Effect $effect,
        private array $actions,
        private array $resourceTypes,
    ) {
    }

    public function effect(): Effect
    {
        return $this->effect;
    }

    /**
     * @return AccountAction[]
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * @return AccountResourceType[]
     */
    public function resourceTypes(): array
    {
        return $this->resourceTypes;
    }
}
