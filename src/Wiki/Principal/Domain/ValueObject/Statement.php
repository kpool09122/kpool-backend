<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final readonly class Statement
{
    /**
     * @param Action[] $actions
     * @param ResourceType[] $resourceTypes
     * @param Condition|null $condition 条件（null は制約なし）
     */
    public function __construct(
        private Effect $effect,
        private array $actions,
        private array $resourceTypes,
        private ?Condition $condition = null,
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

    public function condition(): ?Condition
    {
        return $this->condition;
    }
}
