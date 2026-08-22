<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\SearchTranslationSetMasterWikis;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class SearchTranslationSetMasterWikisAction
{
    public function __construct(private SearchTranslationSetMasterWikisInterface $searchTranslationSetMasterWikis, private LoggerInterface $logger)
    {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(SearchTranslationSetMasterWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SearchTranslationSetMasterWikisInput(
                    resourceType: ResourceType::from($request->resourceType()),
                    keyword: $request->keyword(),
                    limit: $request->limit(),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            $output = new SearchTranslationSetMasterWikisOutput();
            $this->searchTranslationSetMasterWikis->process($input, $output);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
