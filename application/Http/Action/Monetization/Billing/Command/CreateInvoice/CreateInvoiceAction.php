<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Billing\Command\CreateInvoice;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInput;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInterface;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceOutput;
use Source\Monetization\Billing\Domain\Exception\EmptyInvoiceLinesException;
use Source\Monetization\Billing\Domain\Exception\InvalidInvoiceAmountsException;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreateInvoiceAction
{
    public function __construct(
        private CreateInvoiceInterface $createInvoice,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateInvoiceRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateInvoiceRequest $request): JsonResponse
    {
        try {
            try {
                $currency = Currency::from($request->currency());
                $input = new CreateInvoiceInput(
                    orderIdentifier: new OrderIdentifier($request->orderIdentifier()),
                    buyerMonetizationAccountIdentifier: new MonetizationAccountIdentifier($request->buyerMonetizationAccountIdentifier()),
                    lines: array_map(
                        static fn (array $line) => new InvoiceLine(
                            (string) $line['description'],
                            new Money((int) $line['unitPriceAmount'], $currency),
                            (int) $line['quantity'],
                        ),
                        $request->lines()
                    ),
                    shippingCost: new Money($request->shippingCostAmount(), $currency),
                    currency: $currency,
                    discount: $request->discountPercentage() !== null
                        ? new Discount(new Percentage($request->discountPercentage()), $request->discountCode())
                        : null,
                    taxLines: $request->taxLines() !== null
                        ? array_map(
                            static fn (array $line) => new TaxLine(
                                (string) $line['label'],
                                new Percentage((int) $line['rate']),
                                (bool) $line['inclusive'],
                            ),
                            $request->taxLines()
                        )
                        : [],
                    sellerCountry: CountryCode::from($request->sellerCountry()),
                    sellerRegistered: $request->sellerRegistered(),
                    qualifiedInvoiceRequired: $request->qualifiedInvoiceRequired(),
                    buyerCountry: CountryCode::from($request->buyerCountry()),
                    buyerIsBusiness: $request->buyerIsBusiness(),
                    paidByCard: $request->paidByCard(),
                    registrationNumber: $request->registrationNumber(),
                );
                $output = new CreateInvoiceOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->createInvoice->process($input, $output);
                DB::commit();
            } catch (EmptyInvoiceLinesException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('empty_invoice_lines', $language), previous: $e);
            } catch (InvalidInvoiceAmountsException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_invoice_amounts', $language), previous: $e);
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
