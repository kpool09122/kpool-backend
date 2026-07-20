<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;

readonly class ApproveImageDeletion implements ApproveImageDeletionInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
        private WikiRepositoryInterface $wikiRepository,
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private WikiSnapshotRepositoryInterface $wikiSnapshotRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param ApproveImageDeletionInputPort $input
     * @param ApproveImageDeletionOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveImageDeletionInputPort $input, ApproveImageDeletionOutputPort $output): void
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
        if (! $this->policyEvaluator->evaluate($principal, Action::APPROVE, $resource)) {
            throw new DisallowedException();
        }

        $image->approveDeletionRequest($input->principalIdentifier());
        $this->imageRepository->save($image);
        foreach ($this->wikiRepository->findByImageIdentifier($input->imageIdentifier()) as $wiki) {
            $wiki->setImageIdentifier(null);
            $this->wikiRepository->save($wiki);
        }
        foreach ($this->draftWikiRepository->findByImageIdentifier($input->imageIdentifier()) as $draftWiki) {
            $draftWiki->setImageIdentifier(null);
            $this->draftWikiRepository->save($draftWiki);
        }
        foreach ($this->wikiSnapshotRepository->findByImageIdentifier($input->imageIdentifier()) as $wikiSnapshot) {
            $wikiSnapshot->setImageIdentifier(null);
            $this->wikiSnapshotRepository->save($wikiSnapshot);
        }
        $this->imageService->delete($image->imagePath());
        $this->imageRepository->delete($input->imageIdentifier());

        $output->setImage($image);
    }
}
