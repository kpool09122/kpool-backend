<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class DeleteWiki implements DeleteWikiInterface
{
    public function __construct(
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(DeleteWikiInputPort $input, DeleteWikiOutputPort $output): void
    {
        $wiki = $this->draftWikiRepository->findById($input->wikiIdentifier());
        if ($wiki === null) {
            throw new WikiNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: $wiki->resourceType(),
            agencyId: $input->agencyIdentifier() ? (string) $input->agencyIdentifier() : null,
            groupIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->groupIdentifiers(),
            ),
            talentIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->talentIdentifiers(),
            ),
            editorId: $wiki->editorIdentifier() ? (string) $wiki->editorIdentifier() : null,
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::DELETE, $resource)) {
            throw new DisallowedException();
        }

        if ($wiki->status() !== ApprovalStatus::Pending
            && $wiki->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $this->draftWikiRepository->delete($wiki);
    }
}
