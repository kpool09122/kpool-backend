<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class MergeWiki implements MergeWikiInterface
{
    public function __construct(
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface     $policyEvaluator,
    ) {
    }

    /**
     * @param MergeWikiInputPort $input
     * @param MergeWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeWikiInputPort $input, MergeWikiOutputPort $output): void
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

        if (! $this->policyEvaluator->evaluate($principal, Action::MERGE, $resource)) {
            throw new UnauthorizedException();
        }

        $wiki->setBasic($input->basic());
        $wiki->setSections($input->sections());
        $wiki->setThemeColor($input->themeColor());
        $wiki->setMergerIdentifier($input->principalIdentifier());
        $wiki->setMergedAt($input->mergedAt());

        $this->draftWikiRepository->save($wiki);

        $output->setDraftWiki($wiki);
    }
}
