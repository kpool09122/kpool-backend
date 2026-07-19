<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListImageDeletionRequests;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInput;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsOutput;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListImageDeletionRequestsAction
{
    public function __construct(
        private ListImageDeletionRequestsInterface $listImageDeletionRequests,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListImageDeletionRequestsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListImageDeletionRequestsInput(
                    principalIdentifier: $this->wikiContext->principalIdentifier,
                    perPage: $request->perPage(),
                );
                $output = new ListImageDeletionRequestsOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $this->listImageDeletionRequests->process($input, $output);
            } catch (DisallowedException $e) {
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
