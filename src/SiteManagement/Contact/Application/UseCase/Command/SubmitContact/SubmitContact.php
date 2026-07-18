<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Throwable;

readonly class SubmitContact implements SubmitContactInterface
{
    public function __construct(
        private ContactEmailServiceInterface $emailService,
        private ContactFactoryInterface $contactFactory,
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    /**
     * @param SubmitContactInputPort $input
     * @param SubmitContactOutputPort $output
     * @return void
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input, SubmitContactOutputPort $output): void
    {
        $contact = $this->contactFactory->create(
            $input->category(),
            $input->name(),
            $input->email(),
            $input->content(),
            $input->identityIdentifier(),
        );

        $this->contactRepository->save($contact);

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

        $output->setContact($contact);
    }
}
