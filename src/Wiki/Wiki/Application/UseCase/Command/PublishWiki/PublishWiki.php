<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\InconsistentVersionException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\Service\WikiPublicationServiceInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\WikiServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class PublishWiki implements PublishWikiInterface
{
    public function __construct(
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private WikiServiceInterface $wikiService,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private WikiPublicationServiceInterface $wikiPublicationService,
    ) {
    }

    /**
     * @param PublishWikiInputPort $input
     * @param PublishWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws InconsistentVersionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishWikiInputPort $input, PublishWikiOutputPort $output): void
    {
        $wiki = $this->draftWikiRepository->findById($input->wikiIdentifier());

        if ($wiki === null) {
            throw new WikiNotFoundException();
        }

        if ($wiki->status() !== ApprovalStatus::Approved) {
            throw new InvalidStatusException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: $input->resourceType(),
            agencyId: $input->agencyIdentifier() ? (string) $input->agencyIdentifier() : null,
            groupIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->groupIdentifiers(),
            ),
            talentIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->talentIdentifiers(),
            ),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::PUBLISH, $resource)) {
            throw new DisallowedException();
        }

        $publishVersion = $this->wikiService->resolvePublishVersion($wiki);
        if ($publishVersion === null) {
            throw new InconsistentVersionException();
        }

        $output->setWiki($this->wikiPublicationService->publish($wiki, $publishVersion, $input->principalIdentifier(), $input->visitorLocation()));
    }
}
