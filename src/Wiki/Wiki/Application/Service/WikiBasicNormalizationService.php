<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;

readonly class WikiBasicNormalizationService
{
    public function __construct(
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    public function normalize(BasicInterface $basic, Language $language): BasicInterface
    {
        $data = $basic->toArray();
        foreach ($basic->normalizableKeys() as $sourceKey => $normalizedKey) {
            $data[$normalizedKey] = $this->normalizationService->normalize(
                TypedValue::string($data[$sourceKey] ?? ''),
                $language,
            );
        }

        return $basic::fromArray($data);
    }
}
