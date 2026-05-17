<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ImageFactoryInterface
{
    public function create(
        ResourceType        $resourceType,
        TranslationSetIdentifier      $translationSetIdentifier,
        ImagePath           $imagePath,
        int                 $displayOrder,
        string              $sourceUrl,
        string              $sourceName,
        string              $altText,
        PrincipalIdentifier $uploaderIdentifier,
        PrincipalIdentifier $approverIdentifier,
        DateTimeImmutable   $approvedAt,
        RightsConfirmationAgreed $rightsConfirmationAgreed,
    ): Image;
}
