<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface RequestCertificationInputPort
{
    public function resourceType(): ResourceType;

    public function translationSetIdentifier(): TranslationSetIdentifier;

    public function ownerAccountIdentifier(): AccountIdentifier;

    public function requesterPrincipalIdentifier(): PrincipalIdentifier;
}
