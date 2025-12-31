<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class AgencyFactory implements AgencyFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface        $generator,
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Language                 $language,
        AgencyName               $agencyName,
    ): Agency {
        $normalizedName = $this->normalizationService->normalize((string)$agencyName, $language);

        return new Agency(
            new AgencyIdentifier($this->generator->generate()),
            $translationSetIdentifier,
            $language,
            $agencyName,
            $normalizedName,
            new CEO(''),
            '',
            null,
            new Description(''),
            new Version(1),
        );
    }
}
