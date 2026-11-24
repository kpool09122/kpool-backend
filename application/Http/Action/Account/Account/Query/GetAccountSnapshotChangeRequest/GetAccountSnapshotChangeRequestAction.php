<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountSnapshotChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetAccountSnapshotChangeRequestAction
{
    public function __construct(
        private GetAccountSnapshotChangeRequestInterface $getAccountSnapshotChangeRequest,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GetAccountSnapshotChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountSnapshotChangeRequestInput(
                    requestIdentifier: new AccountSnapshotChangeRequestIdentifier($request->requestId()),
                    principal: $this->accountContext->principal(),
                );
                $output = new GetAccountSnapshotChangeRequestOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            try {
                $this->getAccountSnapshotChangeRequest->process($input, $output);
            } catch (AccountSnapshotChangeRequestNotFoundException|AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_snapshot_change_request_not_found', $language), previous: $e);
            } catch (AccountSnapshotNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_snapshot_not_found', $language), previous: $e);
            } catch (AccountSnapshotChangeRequestForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
