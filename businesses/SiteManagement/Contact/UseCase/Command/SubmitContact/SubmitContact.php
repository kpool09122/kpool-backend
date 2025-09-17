<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\UseCase\Command\SubmitContact;

use Businesses\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Businesses\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Businesses\SiteManagement\Contact\UseCase\Exception\FailedToSendEmailException;
use Throwable;

readonly class SubmitContact implements SubmitContactInterface
{
    public function __construct(
        private EmailServiceInterface $emailService,
        private ContactFactoryInterface $contactFactory,
    ) {
    }

    /**
     * @param SubmitContactInputPort $input
     * @return void
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input): void
    {
        $contact = $this->contactFactory->create(
            $input->category(),
            $input->name(),
            $input->email(),
            $input->content(),
        );

        try {
            $this->emailService->sendContactToAdministrator($contact);
        } catch (Throwable $e) {
            throw new FailedToSendEmailException($e->getMessage());
        }

        try {
            $this->emailService->sendContactToUser($contact);
        } catch (Throwable $e) {
            throw new FailedToSendEmailException($e->getMessage());
        }
    }
}
