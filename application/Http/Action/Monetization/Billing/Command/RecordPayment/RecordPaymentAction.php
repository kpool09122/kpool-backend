<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Billing\Command\RecordPayment;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInput;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInterface;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentOutput;
use Source\Monetization\Billing\Domain\Exception\InvoiceNotFoundException;
use Source\Monetization\Billing\Domain\Exception\InvoiceNotPayableException;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Shared\Exception\PaymentAmountMismatchForMatchingException;
use Source\Monetization\Shared\Exception\PaymentCurrencyMismatchForMatchingException;
use Source\Monetization\Shared\Exception\PaymentNotCapturedForMatchingException;
use Source\Monetization\Shared\Exception\PaymentOrderMismatchException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RecordPaymentAction
{
    public function __construct(
        private RecordPaymentInterface $recordPayment,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RecordPaymentRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RecordPaymentRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RecordPaymentInput(
                    new InvoiceIdentifier($request->invoiceId()),
                    new PaymentIdentifier($request->paymentIdentifier()),
                );
                $output = new RecordPaymentOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->recordPayment->process($input, $output);
                DB::commit();
            } catch (InvoiceNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('invoice_not_found', $language), previous: $e);
            } catch (InvoiceNotPayableException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('invoice_not_payable', $language), previous: $e);
            } catch (PaymentNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('payment_not_found', $language), previous: $e);
            } catch (PaymentOrderMismatchException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('payment_order_mismatch', $language), previous: $e);
            } catch (PaymentNotCapturedForMatchingException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('payment_not_captured', $language), previous: $e);
            } catch (PaymentCurrencyMismatchForMatchingException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('payment_currency_mismatch_for_invoice', $language), previous: $e);
            } catch (PaymentAmountMismatchForMatchingException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('payment_amount_mismatch_for_invoice', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

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
