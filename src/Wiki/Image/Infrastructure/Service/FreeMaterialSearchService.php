<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\SearchFreeMaterials\SearchFreeMaterialsRequest;
use JsonException;
use Source\Wiki\Image\Application\DTO\MaterialSearchContext;
use Source\Wiki\Image\Application\DTO\RelatedWikiContext;
use Source\Wiki\Image\Application\Exception\MaterialSearchFailedException;
use Source\Wiki\Image\Application\Service\FreeMaterialSearchServiceInterface;
use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;

final readonly class FreeMaterialSearchService implements FreeMaterialSearchServiceInterface
{
    public function __construct(private GeminiClient $geminiClient)
    {
    }

    /** @return list<FreeMaterialSiteReadModel> */
    public function search(MaterialSearchContext $context): array
    {
        try {
            $response = $this->geminiClient->searchFreeMaterials(new SearchFreeMaterialsRequest(
                $context->title,
                $context->resourceType->value,
                $context->language->value,
                array_map(static fn (RelatedWikiContext $wiki): array => $wiki->toArray(), $context->relatedWikis),
            ));

            return array_map(static fn (array $site): FreeMaterialSiteReadModel => new FreeMaterialSiteReadModel(
                $site['siteName'],
                $site['pageUrl'],
                $site['reason'],
                $site['usageEvidence'],
                $site['usageEvidenceUrl'],
            ), $response->sites());
        } catch (GeminiException|JsonException $e) {
            throw new MaterialSearchFailedException($e);
        }
    }
}
