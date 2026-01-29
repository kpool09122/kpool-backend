<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Service;

use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface SlugGeneratorServiceInterface
{
    public function generate(string $text): Slug;
}
