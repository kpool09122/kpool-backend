<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetCurrentAccountSnapshot;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Throwable;
use ValueError;

readonly class GetCurrentAccountSnapshotAction
{
    public function __construct(private GetAccountSnapshotInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(GetCurrentAccountSnapshotRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountSnapshotInput(new AccountIdentifier($request->accountIdentifier()), $this->accountContext->principal());
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail:error_message('disallowed', $language), previous:$e);
            }

            try {
                $snapshot = $this->useCase->process($input);

                return response()->json($snapshot->toArray(), 200, ['Cache-Control' => 'private, no-store']);

            } catch (AccountSnapshotForbiddenException $e) {
                throw new ForbiddenHttpException(detail:error_message('disallowed', $language), previous:$e);
            } catch (AccountSnapshotNotFoundException $e) {
                throw new NotFoundHttpException(detail:error_message('account_snapshot_not_found', $language), previous:$e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException|ConflictHttpException $e) {
            $this->logger->error((string)$e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string)$e);

            throw new InternalServerErrorHttpException(previous:$e);
        }
    }
}
