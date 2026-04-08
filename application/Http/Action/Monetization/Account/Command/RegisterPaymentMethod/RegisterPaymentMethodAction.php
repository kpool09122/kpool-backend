<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\RegisterPaymentMethod;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RegisterPaymentMethodAction
{
    public function __construct(
        private RegisterPaymentMethodInterface $registerPaymentMethod,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RegisterPaymentMethodRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RegisterPaymentMethodRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RegisterPaymentMethodInput(
                    new MonetizationAccountIdentifier($request->monetizationAccountId()),
                    new PaymentMethodId($request->paymentMethodId()),
                    PaymentMethodType::from($request->type()),
                );
                $output = new RegisterPaymentMethodOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->registerPaymentMethod->process($input, $output);
                DB::commit();
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
}
