<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\Service\WikiContentKeyService;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class SubmitWiki implements SubmitWikiInterface
{
    public function __construct(
        private WikiContentKeyService $wikiContentKeyService,
        private DraftWikiRepositoryInterface   $draftWikiRepository,
        private WikiHistoryRepositoryInterface $wikiHistoryRepository,
        private WikiHistoryFactoryInterface    $wikiHistoryFactory,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param SubmitWikiInputPort $input
     * @param SubmitWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(SubmitWikiInputPort $input, SubmitWikiOutputPort $output): void
    {
        $targets = $input->targets();
        $wikiIdentifiers = array_map(static fn (SubmitWikiTarget $target): DraftWikiIdentifier => $target->wikiIdentifier(), $targets);
        $wikisById = [];
        foreach ($this->draftWikiRepository->findByIds($wikiIdentifiers) as $wiki) {
            $wikisById[strtolower((string) $wiki->wikiIdentifier())] = $wiki;
        }

        $wikis = [];
        foreach ($wikiIdentifiers as $wikiIdentifier) {
            $wiki = $wikisById[strtolower((string) $wikiIdentifier)] ?? null;
            if ($wiki === null) {
                throw new WikiNotFoundException();
            }
            $wikis[] = $wiki;
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        foreach ($targets as $index => $target) {
            $wiki = $wikis[$index];
            $resource = new Resource(
                type: $target->resourceType(),
                agencyId: $target->agencyIdentifier() ? (string) $target->agencyIdentifier() : null,
                groupIds: array_map(
                    static fn (WikiIdentifier $id) => (string) $id,
                    $target->groupIdentifiers(),
                ),
                talentIds: array_map(
                    static fn (WikiIdentifier $id) => (string) $id,
                    $target->talentIdentifiers(),
                ),
            );

            if (! $this->policyEvaluator->evaluate($principal, Action::SUBMIT, $resource)) {
                throw new DisallowedException();
            }

            if ($wiki->status() !== ApprovalStatus::Pending
            && $wiki->status() !== ApprovalStatus::Rejected) {
                throw new InvalidStatusException();
            }
        }

        foreach ($wikis as $wiki) {
            $previousStatus = $wiki->status();
            $wiki->setStatus(ApprovalStatus::UnderReview);

            $wiki->setSections($this->wikiContentKeyService->assignMissingKeys($wiki->sections()));
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
                visitorLocation: $input->visitorLocation(),
                translationSetIdentifier: $wiki->translationSetIdentifier(),
                language: $wiki->language(),
                resourceType: $wiki->resourceType(),
                actorIdentityIdentifier: $principal->identityIdentifier(),
            );
            $this->wikiHistoryRepository->save($history);

            $output->addDraftWiki($wiki);
        }
    }
}
