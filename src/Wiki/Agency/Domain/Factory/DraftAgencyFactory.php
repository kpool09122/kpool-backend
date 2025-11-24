<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

class DraftAgencyFactory implements DraftAgencyFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier          $editorIdentifier,
        Language                  $language,
        AgencyName                $agencyName,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftAgency {
        return new DraftAgency(
            new AgencyIdentifier($this->ulidGenerator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $language,
            $agencyName,
            new CEO(''),
            null,
            new Description(''),
            ApprovalStatus::Pending,
        );
    }
}
