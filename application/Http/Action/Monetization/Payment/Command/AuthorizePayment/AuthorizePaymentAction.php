<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\AuthorizePayment;

use Application\Http\Exceptions\ConflictHttpException;
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
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType as RegisteredPaymentMethodType;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentOutput;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AuthorizePaymentAction
{
    public function __construct(
        private AuthorizePaymentInterface $authorizePayment,
        private RegisterPaymentMethodInterface $registerPaymentMethod,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param AuthorizePaymentRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(AuthorizePaymentRequest $request): JsonResponse
    {
        try {
            try {
                $input = new AuthorizePaymentInput(
                    new OrderIdentifier($request->orderId()),
                    new MonetizationAccountIdentifier($request->buyerMonetizationAccountId()),
                    new Money($request->amount(), Currency::from($request->currency())),
                    new PaymentMethod(
                        new PaymentMethodIdentifier($request->paymentMethodId()),
                        PaymentMethodType::from($request->paymentMethodType()),
                        $request->paymentMethodLabel(),
                        $request->paymentMethodRecurringEnabled(),
                    ),
                );
                $output = new AuthorizePaymentOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->authorizePayment->process($input, $output);

                $registerInput = new RegisterPaymentMethodInput(
                    new MonetizationAccountIdentifier($request->buyerMonetizationAccountId()),
                    new PaymentMethodId($request->stripePaymentMethodId()),
                    RegisteredPaymentMethodType::CARD,
                );
                $registerOutput = new RegisterPaymentMethodOutput();
                $this->registerPaymentMethod->process($registerInput, $registerOutput);

                DB::commit();
            } catch (InvalidPaymentStatusException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('invalid_payment_status', $language), previous: $e);
            } catch (PaymentGatewayException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: error_message('payment_gateway_error', $language), previous: $e);
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
