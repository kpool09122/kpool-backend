<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;

readonly class DeleteImage implements DeleteImageInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param DeleteImageInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(DeleteImageInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $image = $this->imageRepository->findById($input->imageIdentifier());
        if ($image === null) {
            throw new ImageNotFoundException();
        }

        $resource = $this->imageAuthorizationResourceBuilder->buildFromImage($image);
        if (! $this->policyEvaluator->evaluate($principal, Action::DELETE, $resource)) {
            throw new DisallowedException();
        }

        $this->imageRepository->delete($input->imageIdentifier());
    }
}
