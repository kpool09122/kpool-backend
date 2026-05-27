<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListVersionInconsistentWikis;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class ListVersionInconsistentWikisAction
{
    public function __construct(
        private ListVersionInconsistentWikisInterface $listVersionInconsistentWikis,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListVersionInconsistentWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListVersionInconsistentWikisInput(
                    perPage: $request->perPage(),
                    resourceType: $request->resourceType() === null ? null : ResourceType::from($request->resourceType()),
                    sort: $request->sort(),
                    order: $request->order(),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            $output = new ListVersionInconsistentWikisOutput();
            $this->listVersionInconsistentWikis->process($input, $output);
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
