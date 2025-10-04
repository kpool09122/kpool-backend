<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;

class AgencyFactory implements AgencyFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        AgencyName $agencyName,
    ): Agency {
        return new Agency(
            new AgencyIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier,
            $translation,
            $agencyName,
            new CEO(''),
            null,
            new Description(''),
        );
    }
}
