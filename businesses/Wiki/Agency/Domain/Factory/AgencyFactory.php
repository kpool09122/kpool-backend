<?php

namespace Businesses\Wiki\Agency\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
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
        AgencyName $agencyName,
    ): Agency {
        return new Agency(
            new AgencyIdentifier($this->ulidGenerator->generate()),
            $agencyName,
            new CEO(''),
            null,
            new Description(''),
        );
    }
}
