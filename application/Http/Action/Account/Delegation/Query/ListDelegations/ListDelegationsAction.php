<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Query\ListDelegations;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInput;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInterface;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListDelegationsAction
{
    public function __construct(
        private ListDelegationsInterface $listDelegations,
        private AccountContext $accountContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListDelegationsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListDelegationsInput(
                    principal: $this->accountContext->principal(),
                    status: $request->status(),
                    viewerRole: $request->viewerRole(),
                    perPage: $request->perPage(),
                    page: $request->page(),
                );
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            try {
                $output = new ListDelegationsOutput();
                $this->listDelegations->process($input, $output);
            } catch (DisallowedDelegationOperationException $exception) {
                throw new ForbiddenHttpException(detail: error_message('disallowed_delegation_operation', $request->language()), previous: $exception);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
