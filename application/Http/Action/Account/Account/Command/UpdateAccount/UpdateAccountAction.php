<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\UpdateAccount;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Phone;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class UpdateAccountAction
{
    public function __construct(
        private UpdateAccountInterface $updateAccount,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param UpdateAccountRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(UpdateAccountRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $address = $request->address();
                $input = new UpdateAccountInput(
                    accountIdentifier: new AccountIdentifier($request->accountId()),
                    principal: $this->accountContext->principal(),
                    accountName: new AccountName($request->accountName()),
                    phone: $request->phone() !== null ? new Phone($request->phone()) : null,
                    addressCountryCode: self::nullableString($address, 'countryCode'),
                    addressAdministrativeAreaCode: self::nullableString($address, 'administrativeAreaCode'),
                    addressPostalCode: self::nullableString($address, 'postalCode'),
                    addressLocality: self::nullableString($address, 'locality'),
                    addressLine1: self::nullableString($address, 'addressLine1'),
                    addressLine2: self::nullableString($address, 'addressLine2'),
                );
                $output = new UpdateAccountOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->updateAccount->process($input, $output);
                DB::commit();
            } catch (InvalidArgumentException|ValueError $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_not_found', $language), previous: $e);
            } catch (AccountUpdateForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('account_update_forbidden', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }

    /** @param array<string, mixed>|null $values */
    private static function nullableString(?array $values, string $key): ?string
    {
        if ($values === null || ! array_key_exists($key, $values) || $values[$key] === null) {
            return null;
        }

        return (string) $values[$key];
    }
}
