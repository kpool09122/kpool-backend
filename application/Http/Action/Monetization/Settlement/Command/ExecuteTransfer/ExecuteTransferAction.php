<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Settlement\Command\ExecuteTransfer;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInput;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInterface;
use Source\Monetization\Settlement\Domain\Exception\TransferNotFoundException;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ExecuteTransferAction
{
    public function __construct(
        private ExecuteTransferInterface $executeTransfer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ExecuteTransferRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ExecuteTransferRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ExecuteTransferInput(
                    new TransferIdentifier($request->transferId()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->executeTransfer->process($input);
                DB::commit();
            } catch (TransferNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('transfer_not_found', $language), previous: $e);
            } catch (MonetizationAccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('monetization_account_not_found', $language), previous: $e);
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

        return response()->json([], Response::HTTP_OK);
    }
}
