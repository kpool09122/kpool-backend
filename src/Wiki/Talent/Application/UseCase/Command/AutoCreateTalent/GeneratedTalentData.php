<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GeneratedTalentData
{
    /**
     * @param string|null $alphabetName
     * @param string|null $realName
     * @param string|null $birthday
     * @param string|null $description
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $realName,
        private ?string $birthday,
        private ?string $description,
        private array $sources,
    ) {
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function realName(): ?string
    {
        return $this->realName;
    }

    public function birthday(): ?string
    {
        return $this->birthday;
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
