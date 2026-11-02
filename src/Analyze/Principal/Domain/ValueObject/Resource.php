<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\ValueObject;

final readonly class Resource
{
    public function __construct(private ResourceType $type = ResourceType::ANALYSIS)
    {
    }

    public function type(): ResourceType
    {
        return $this->type;
    }
}
