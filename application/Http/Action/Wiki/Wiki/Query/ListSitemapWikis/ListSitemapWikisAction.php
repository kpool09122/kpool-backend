<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListSitemapWikis;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisOutput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListSitemapWikisAction
{
    public function __construct(
        private ListSitemapWikisInterface $listSitemapWikis,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListSitemapWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListSitemapWikisInput(
                    perPage: $request->perPage() ?? 500,
                    after: $request->cursor() === null ? null : new WikiIdentifier($request->cursor()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(previous: $e);
            }

            $output = new ListSitemapWikisOutput();
            $this->listSitemapWikis->process($input, $output);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
