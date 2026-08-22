<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class RequestCertificationInput implements RequestCertificationInputPort
{
    public function __construct(
        private ResourceType      $resourceType,
        private TranslationSetIdentifier $translationSetIdentifier,
        private AccountIdentifier $ownerAccountIdentifier,
        private PrincipalIdentifier $requesterPrincipalIdentifier,
    ) {
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function ownerAccountIdentifier(): AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }

    public function requesterPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->requesterPrincipalIdentifier;
    }
}
