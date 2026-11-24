<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetLatestAccountSnapshotChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestOutput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetLatestAccountSnapshotChangeRequestAction
{
    public function __construct(
        private GetLatestAccountSnapshotChangeRequestInterface $getLatestAccountSnapshotChangeRequest,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param GetLatestAccountSnapshotChangeRequestRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetLatestAccountSnapshotChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetLatestAccountSnapshotChangeRequestInput(
                    accountIdentifier: new AccountIdentifier($request->accountIdentifier()),
                    principal: $this->accountContext->principal(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            try {
                $output = new GetLatestAccountSnapshotChangeRequestOutput();
                $this->getLatestAccountSnapshotChangeRequest->process($input, $output);
            } catch (AccountSnapshotNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_snapshot_not_found', $language), previous: $e);
            } catch (AccountSnapshotChangeRequestForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
