<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Query;

use Source\Wiki\Image\Application\DTO\MaterialSearchContext;
use Source\Wiki\Image\Application\DTO\RelatedWikiContext;
use Source\Wiki\Image\Application\Service\FreeMaterialSearchServiceInterface;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\RelatedWikiInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInputPort;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInterface;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsOutputPort;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final readonly class SearchFreeMaterials implements SearchFreeMaterialsInterface
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PolicyEvaluatorInterface $policyEvaluator, private FreeMaterialSearchServiceInterface $freeMaterialSearchService)
    {
    }

    public function process(SearchFreeMaterialsInputPort $input, SearchFreeMaterialsOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::SEARCH_FREE_MATERIALS, new Resource(ResourceType::IMAGE))) {
            throw new DisallowedException();
        }
        $title = trim($input->title() ?? '');
        $context = new MaterialSearchContext(
            $title !== '' ? $title : trim($input->displayName() ?? ''),
            $input->resourceType(),
            $input->language(),
            array_map(static fn (RelatedWikiInput $wiki): RelatedWikiContext => new RelatedWikiContext(
                $wiki->identifier,
                $wiki->title,
                $wiki->resourceType,
                $wiki->relationType,
            ), $input->relatedWikis()),
        );
        $output->output($this->freeMaterialSearchService->search($context));
    }
}
