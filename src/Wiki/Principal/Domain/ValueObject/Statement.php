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
     */
    public function __construct(
        private Effect $effect,
        private array $actions,
        private array $resourceTypes,
        private ScopeCondition $scopeCondition = ScopeCondition::NONE,
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

    public function scopeCondition(): ScopeCondition
    {
        return $this->scopeCondition;
    }
}
