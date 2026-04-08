<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use DateTimeImmutable;
use Source\Monetization\Billing\Domain\Exception\EmptyInvoiceLinesException;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactoryInterface;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyServiceInterface;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;

readonly class CreateInvoice implements CreateInvoiceInterface
{
    private const string SHIPPING_DESCRIPTION = 'Shipping';
    private const int DEFAULT_DUE_DAYS = 14;

    public function __construct(
        private InvoiceFactoryInterface $invoiceFactory,
        private InvoiceRepositoryInterface $invoiceRepository,
        private TaxDocumentPolicyServiceInterface $taxDocumentPolicyService,
    ) {
    }

    public function process(CreateInvoiceInputPort $input, CreateInvoiceOutputPort $output): void
    {
        if ($input->lines() === []) {
            throw new EmptyInvoiceLinesException();
        }

        $invoiceLines = $this->buildInvoiceLines($input);
        $issuedAt = new DateTimeImmutable();
        $dueDate = $issuedAt->modify('+' . self::DEFAULT_DUE_DAYS . ' days');

        $invoice = $this->invoiceFactory->create(
            $input->orderIdentifier(),
            $input->buyerMonetizationAccountIdentifier(),
            $invoiceLines,
            $input->currency(),
            $issuedAt,
            $dueDate,
            $input->discount(),
            $input->taxLines(),
        );

        $taxDocument = $this->taxDocumentPolicyService->decide(
            $input->sellerCountry(),
            $input->sellerRegistered(),
            $input->qualifiedInvoiceRequired(),
            $input->buyerCountry(),
            $input->buyerIsBusiness(),
            $input->paidByCard(),
            $input->registrationNumber(),
            $dueDate,
            null,
        );

        $invoice->setTaxDocument($taxDocument);
        $this->invoiceRepository->save($invoice);

        $output->setInvoice($invoice);
    }

    /**
     * @return InvoiceLine[]
     */
    private function buildInvoiceLines(CreateInvoiceInputPort $input): array
    {
        $invoiceLines = $input->lines();

        if ($input->shippingCost()->amount() > 0) {
            $invoiceLines[] = new InvoiceLine(
                self::SHIPPING_DESCRIPTION,
                $input->shippingCost(),
                1,
            );
        }

        return $invoiceLines;
    }
}
