<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\EditWiki;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class EditWiki implements EditWikiInterface
{
    public function __construct(
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface     $policyEvaluator,
    ) {
    }

    /**
     * @param EditWikiInputPort $input
     * @return DraftWiki
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(EditWikiInputPort $input): DraftWiki
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

        if (! $this->policyEvaluator->evaluate($principal, Action::EDIT, $resource)) {
            throw new DisallowedException();
        }

        $wiki->setBasic($input->basic());
        $wiki->setSections($input->sections());
        $wiki->setThemeColor($input->themeColor());

        $this->draftWikiRepository->save($wiki);

        return $wiki;
    }
}
