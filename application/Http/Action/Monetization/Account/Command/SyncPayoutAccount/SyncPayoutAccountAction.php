<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\SyncPayoutAccount;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInput;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInterface;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SyncPayoutAccountAction
{
    public function __construct(
        private SyncPayoutAccountInterface $syncPayoutAccount,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SyncPayoutAccountRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SyncPayoutAccountRequest $request): JsonResponse
    {
        try {
            try {
                $accountHolderType = $request->accountHolderType();

                $input = new SyncPayoutAccountInput(
                    connectedAccountId: new ConnectedAccountId($request->connectedAccountId()),
                    externalAccountId: new ExternalAccountId($request->externalAccountId()),
                    eventType: $request->eventType(),
                    bankName: $request->bankName(),
                    last4: $request->last4(),
                    country: $request->country(),
                    currency: $request->currency(),
                    accountHolderType: $accountHolderType !== null
                        ? AccountHolderType::from($accountHolderType)
                        : null,
                    isDefault: $request->isDefault(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->syncPayoutAccount->process($input);
                DB::commit();
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
