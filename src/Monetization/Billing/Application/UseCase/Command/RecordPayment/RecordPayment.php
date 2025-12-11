<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use DateTimeImmutable;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\Exception\InvoiceNotFoundException;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Shared\Service\PaymentMatcherServiceInterface;

readonly class RecordPayment implements RecordPaymentInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentMatcherServiceInterface $paymentMatcherService,
    ) {
    }

    public function process(RecordPaymentInputPort $input): Invoice
    {
        $invoice = $this->invoiceRepository->findById($input->invoiceIdentifier());

        if ($invoice === null) {
            throw new InvoiceNotFoundException($input->invoiceIdentifier());
        }

        $payment = $this->paymentRepository->findById($input->paymentIdentifier());

        if ($payment === null) {
            throw new PaymentNotFoundException($input->paymentIdentifier());
        }

        $this->paymentMatcherService->match($invoice, $payment, new DateTimeImmutable());

        $this->invoiceRepository->save($invoice);

        return $invoice;
    }
}
