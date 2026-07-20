<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

final readonly class Statement
{
    /**
     * @param Action[] $actions
     * @param ResourceType[] $resourceTypes
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
     * @return Action[]
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * @return ResourceType[]
     */
    public function resourceTypes(): array
    {
        return $this->resourceTypes;
    }
}
