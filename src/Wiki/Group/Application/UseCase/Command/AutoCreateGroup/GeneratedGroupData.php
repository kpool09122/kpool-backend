<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GeneratedGroupData
{
    /**
     * @param string|null $alphabetName
     * @param string|null $description
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $description,
        private array $sources,
    ) {
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
