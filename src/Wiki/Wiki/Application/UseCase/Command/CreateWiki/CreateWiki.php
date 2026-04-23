<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class CreateWiki implements CreateWikiInterface
{
    public function __construct(
        private DraftWikiFactoryInterface    $wikiFactory,
        private WikiRepositoryInterface      $wikiRepository,
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface     $policyEvaluator,
    ) {
    }

    /**
     * @param CreateWikiInputPort $input
     * @param CreateWikiOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws DuplicateSlugException
     */
    public function process(CreateWikiInputPort $input, CreateWikiOutputPort $output): void
    {
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

        if (! $this->policyEvaluator->evaluate($principal, Action::CREATE, $resource)) {
            throw new DisallowedException();
        }

        if ($this->wikiRepository->existsBySlug($input->slug())) {
            throw new DuplicateSlugException();
        }

        $wiki = $this->wikiFactory->create(
            $input->principalIdentifier(),
            $input->language(),
            $input->basic(),
            $input->slug(),
        );

        if ($input->publishedWikiIdentifier()) {
            $publishedWiki = $this->wikiRepository->findById($input->publishedWikiIdentifier());
            if ($publishedWiki) {
                $wiki->setPublishedWikiIdentifier($publishedWiki->wikiIdentifier());
            }
        }

        $wiki->setSections($input->sections());
        $wiki->setThemeColor($input->themeColor());

        $this->draftWikiRepository->save($wiki);

        $output->setDraftWiki($wiki);
    }
}
