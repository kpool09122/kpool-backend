<?php

namespace Businesses\Wiki\Agency\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use Businesses\Wiki\Agency\Domain\ValueObject\Description;

class AgencyFactory implements AgencyFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        Translation $translation,
        AgencyName $agencyName,
    ): Agency {
        return new Agency(
            new AgencyIdentifier($this->ulidGenerator->generate()),
            $translation,
            $agencyName,
            new CEO(''),
            null,
            new Description(''),
        );
    }
}
