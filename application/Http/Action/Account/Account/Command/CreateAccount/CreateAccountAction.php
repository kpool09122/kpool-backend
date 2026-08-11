<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\CreateAccount;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountOutput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\Phone;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class CreateAccountAction
{
    public function __construct(
        private CreateAccountInterface $createAccount,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateAccountRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateAccountRequest $request): JsonResponse
    {
        try {
            try {
                $address = $request->address();
                $input = new CreateAccountInput(
                    email: new Email($request->email()),
                    accountType: AccountType::from($request->accountType()),
                    accountName: new AccountName($request->accountName()),
                    identityIdentifier: $request->identityIdentifier() !== null
                        ? new IdentityIdentifier($request->identityIdentifier())
                        : null,
                    language: Language::from($request->language()),
                    phone: $request->phone() !== null ? new Phone($request->phone()) : null,
                    addressCountryCode: self::nullableString($address, 'countryCode'),
                    addressAdministrativeAreaCode: self::nullableString($address, 'administrativeAreaCode'),
                    addressPostalCode: self::nullableString($address, 'postalCode'),
                    addressLocality: self::nullableString($address, 'locality'),
                    addressLine1: self::nullableString($address, 'addressLine1'),
                    addressLine2: self::nullableString($address, 'addressLine2'),
                );
                $output = new CreateAccountOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->createAccount->process($input, $output);
                DB::commit();
            } catch (InvalidArgumentException|ValueError $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
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
