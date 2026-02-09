<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\ExistsApprovedDraftWikiException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\WikiServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class ApproveWiki implements ApproveWikiInterface
{
    public function __construct(
        private DraftWikiRepositoryInterface   $draftWikiRepository,
        private WikiRepositoryInterface        $wikiRepository,
        private WikiServiceInterface           $wikiService,
        private WikiHistoryRepositoryInterface $wikiHistoryRepository,
        private WikiHistoryFactoryInterface    $wikiHistoryFactory,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param ApproveWikiInputPort $input
     * @param ApproveWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws ExistsApprovedDraftWikiException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws DuplicateSlugException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveWikiInputPort $input, ApproveWikiOutputPort $output): void
    {
        $wiki = $this->draftWikiRepository->findById($input->wikiIdentifier());

        if ($wiki === null) {
            throw new WikiNotFoundException();
        }

        if ($wiki->status() !== ApprovalStatus::UnderReview) {
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

        if (! $this->policyEvaluator->evaluate($principal, Action::APPROVE, $resource)) {
            throw new DisallowedException();
        }

        if ($this->wikiRepository->existsBySlug($wiki->slug())) {
            throw new DuplicateSlugException();
        }

        // 同じ翻訳セットの別版で、承認済みだが公開されていないDraftWikiが存在するかチェック
        if ($this->wikiService->existsApprovedDraftWiki($wiki->translationSetIdentifier(), $wiki->wikiIdentifier())) {
            throw new ExistsApprovedDraftWikiException();
        }

        $previousStatus = $wiki->status();
        $wiki->setStatus(ApprovalStatus::Approved);
        $wiki->setApproverIdentifier($input->principalIdentifier());
        $wiki->setApprovedAt(new DateTimeImmutable());

        $this->draftWikiRepository->save($wiki);

        $history = $this->wikiHistoryFactory->create(
            actionType: HistoryActionType::DraftStatusChange,
            actorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $wiki->editorIdentifier(),
            wikiIdentifier: $wiki->publishedWikiIdentifier(),
            draftWikiIdentifier: new DraftWikiIdentifier((string) $wiki->wikiIdentifier()),
            fromStatus: $previousStatus,
            toStatus: $wiki->status(),
            fromVersion: null,
            toVersion: null,
            subjectName: $wiki->basic()->name(),
        );
        $this->wikiHistoryRepository->save($history);

        $output->setDraftWiki($wiki);
    }
}
