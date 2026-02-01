<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UnhideImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;

readonly class UnhideImage implements UnhideImageInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param UnhideImageInputPort $input
     * @return Image
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(UnhideImageInputPort $input): Image
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
        if (! $this->policyEvaluator->evaluate($principal, Action::UNHIDE, $resource)) {
            throw new DisallowedException();
        }

        $image->unhide();
        $this->imageRepository->save($image);

        return $image;
    }
}
