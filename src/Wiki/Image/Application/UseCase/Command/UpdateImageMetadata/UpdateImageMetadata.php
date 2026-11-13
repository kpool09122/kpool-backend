<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata;

use DateTimeImmutable;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;

readonly class UpdateImageMetadata implements UpdateImageMetadataInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param UpdateImageMetadataInputPort $input
     * @param UpdateImageMetadataOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(UpdateImageMetadataInputPort $input, UpdateImageMetadataOutputPort $output): void
    {
        $image = $this->imageRepository->findById($input->imageIdentifier());
        if ($image === null) {
            throw new ImageNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = $this->imageAuthorizationResourceBuilder->buildFromImage($image);
        if (! $this->policyEvaluator->evaluate($principal, Action::UPDATE_IMAGE_METADATA, $resource)) {
            throw new DisallowedException();
        }

        $image->setSourceName($input->sourceName());
        $image->setSourceUrl($input->sourceUrl());
        $image->setAttribution($input->author(), $input->license());
        $image->setAltText($input->altText());
        $image->setDisplayOrder($input->displayOrder());
        $image->setUpdaterIdentifier($input->principalIdentifier());
        $image->setUpdatedAt(new DateTimeImmutable());
        $this->imageRepository->save($image);

        $output->setImage($image);
    }
}
