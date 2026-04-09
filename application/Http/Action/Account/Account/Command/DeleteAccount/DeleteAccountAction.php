<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\DeleteAccount;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInput;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountOutput;
use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeleteAccountAction
{
    public function __construct(
        private DeleteAccountInterface $deleteAccount,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param DeleteAccountRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DeleteAccountRequest $request): JsonResponse
    {
        try {
            try {
                $input = new DeleteAccountInput(
                    accountIdentifier: new AccountIdentifier($request->accountId()),
                );
                $output = new DeleteAccountOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->deleteAccount->process($input, $output);
                DB::commit();
            } catch (AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_not_found', $language), previous: $e);
            } catch (AccountDeletionBlockedException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('account_deletion_blocked', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
