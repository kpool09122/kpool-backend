<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\SearchMasterWikis;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class SearchMasterWikisAction
{
    public function __construct(
        private SearchMasterWikisInterface $searchMasterWikis,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SearchMasterWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SearchMasterWikisInput(
                    language: Language::from($request->language()),
                    resourceType: ResourceType::from($request->resourceType()),
                    keyword: $request->keyword(),
                    limit: $request->limit(),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            $output = new SearchMasterWikisOutput();
            $this->searchMasterWikis->process($input, $output);
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
