<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\RefundPayment;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentOutput;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Exception\RefundCurrencyMismatchException;
use Source\Monetization\Payment\Domain\Exception\RefundExceedsCapturedAmountException;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RefundPaymentAction
{
    public function __construct(
        private RefundPaymentInterface $refundPayment,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RefundPaymentRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RefundPaymentRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RefundPaymentInput(
                    new PaymentIdentifier($request->paymentId()),
                    new Money($request->refundAmount(), Currency::from($request->refundCurrency())),
                    $request->reason(),
                );
                $output = new RefundPaymentOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->refundPayment->process($input, $output);
                DB::commit();
            } catch (PaymentNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('payment_not_found', $language), previous: $e);
            } catch (InvalidPaymentStatusException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('invalid_payment_status', $language), previous: $e);
            } catch (RefundExceedsCapturedAmountException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new UnprocessableEntityHttpException(detail: error_message('refund_exceeds_captured_amount', $language), previous: $e);
            } catch (RefundCurrencyMismatchException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new UnprocessableEntityHttpException(detail: error_message('refund_currency_mismatch', $language), previous: $e);
            } catch (PaymentGatewayException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: error_message('payment_gateway_error', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException|ConflictHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
