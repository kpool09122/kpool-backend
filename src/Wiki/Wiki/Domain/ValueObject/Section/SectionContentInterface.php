<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Section;

interface SectionContentInterface
{
    public function key(): ?string;

    public function displayOrder(): int;
}
