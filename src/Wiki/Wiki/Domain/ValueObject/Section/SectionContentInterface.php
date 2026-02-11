<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Section;

interface SectionContentInterface
{
    public function displayOrder(): int;
}
