<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\SearchFreeMaterials;

use Application\Http\Action\Support\RequestValue;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\ServiceUnavailableHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Image\Application\Exception\MaterialSearchFailedException;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\RelatedWikiInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInterface;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsOutput;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Throwable;
use ValueError;

final readonly class SearchFreeMaterialsAction
{
    public function __construct(private SearchFreeMaterialsInterface $searchFreeMaterials, private WikiContext $wikiContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(SearchFreeMaterialsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SearchFreeMaterialsInput(
                    $this->wikiContext->principalIdentifier,
                    $request->title(),
                    $request->displayName(),
                    ResourceType::from($request->resourceType()),
                    Language::from($request->language()),
                    array_map(static fn (array $wiki): RelatedWikiInput => new RelatedWikiInput(
                        new WikiIdentifier(RequestValue::string($wiki['identifier'])),
                        RequestValue::string($wiki['title']),
                        ResourceType::from(RequestValue::string($wiki['resourceType'])),
                        RequestValue::string($wiki['relationType']),
                    ), $request->relatedWikis()),
                );
                $output = new SearchFreeMaterialsOutput();

                $this->searchFreeMaterials->process($input, $output);
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_material_search_context', $request->language()), previous: $e);
            } catch (DisallowedException|PrincipalNotFoundException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (MaterialSearchFailedException $e) {
                // Do not log upstream response text or a request URI containing the API key.
                $this->logger->warning('Wiki material search failed.');

                throw new ServiceUnavailableHttpException(previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (ServiceUnavailableHttpException $e) {
            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray());
    }
}
