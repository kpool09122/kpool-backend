<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UploadImage;

use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Factory\DraftImageFactoryInterface;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;

readonly class UploadImage implements UploadImageInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private DraftImageFactoryInterface $draftImageFactory,
        private DraftImageRepositoryInterface $draftImageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param UploadImageInputPort $input
     * @return DraftImage
     * @throws InvalidBase64ImageException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(UploadImageInputPort $input): DraftImage
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $action = $input->publishedImageIdentifier() === null ? Action::CREATE : Action::EDIT;
        $resource = $this->imageAuthorizationResourceBuilder->buildFromDraftResource(
            $input->resourceType(),
            $input->wikiIdentifier(),
        );

        if (! $this->policyEvaluator->evaluate($principal, $action, $resource)) {
            throw new DisallowedException();
        }

        $uploadResult = $this->imageService->upload($input->base64EncodedImage());

        $draftImage = $this->draftImageFactory->create(
            $input->publishedImageIdentifier(),
            $input->resourceType(),
            $input->wikiIdentifier(),
            $input->principalIdentifier(),
            $uploadResult->resized,
            $input->imageUsage(),
            $input->displayOrder(),
            $input->sourceUrl(),
            $input->sourceName(),
            $input->altText(),
            $input->agreedToTermsAt(),
        );

        $this->draftImageRepository->save($draftImage);

        return $draftImage;
    }
}
