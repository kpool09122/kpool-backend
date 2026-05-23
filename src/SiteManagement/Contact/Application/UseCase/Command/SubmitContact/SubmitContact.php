<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Throwable;

readonly class SubmitContact implements SubmitContactInterface
{
    public function __construct(
        private EmailServiceInterface $emailService,
        private ContactFactoryInterface $contactFactory,
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    /**
     * @param SubmitContactInputPort $input
     * @return ContactIdentifier
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input): ContactIdentifier
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

        return $contact->contactIdentifier();
    }
}
