<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Service;

use Source\Shared\Domain\ValueObject\Language;

interface NormalizationServiceInterface
{
    public function normalize(string $value, Language $language): string;
}
