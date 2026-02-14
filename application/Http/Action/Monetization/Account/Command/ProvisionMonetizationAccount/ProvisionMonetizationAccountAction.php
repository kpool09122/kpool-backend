<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\ProvisionMonetizationAccount;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\Exception\MonetizationAccountAlreadyExistsException;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountInput;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountInterface;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountOutput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ProvisionMonetizationAccountAction
{
    public function __construct(
        private ProvisionMonetizationAccountInterface $provisionMonetizationAccount,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ProvisionMonetizationAccountRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ProvisionMonetizationAccountRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ProvisionMonetizationAccountInput(
                    new AccountIdentifier($request->accountId()),
                );
                $output = new ProvisionMonetizationAccountOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->provisionMonetizationAccount->process($input, $output);
                DB::commit();
            } catch (MonetizationAccountAlreadyExistsException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('monetization_account_already_exists', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (UnprocessableEntityHttpException|ConflictHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
