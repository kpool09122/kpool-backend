<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListWikis;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class ListWikisAction
{
    public function __construct(
        private ListWikisInterface $listWikis,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListWikisInput(
                    language: Language::from($request->language()),
                    perPage: $request->perPage(),
                    resourceType: $request->resourceType() === null ? null : ResourceType::from($request->resourceType()),
                    keyword: $request->keyword(),
                    sort: $request->sort(),
                    order: $request->order(),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            $output = new ListWikisOutput();
            $this->listWikis->process($input, $output);
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
