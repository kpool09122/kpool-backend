<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use DateTimeImmutable;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;
use Throwable;

readonly class ReplyContact implements ReplyContactInterface
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private ReplyContactFactoryInterface $replyContactFactory,
        private ReplyContactRepositoryInterface $replyContactRepository,
        private EmailServiceInterface $emailService,
    ) {
    }

    /**
     * @throws ContactNotFoundException
     * @throws FailedToSendEmailException
     */
    public function process(ReplyContactInputPort $input): void
    {
        $contact = $this->contactRepository->findById($input->contactIdentifier());
        if ($contact === null) {
            throw new ContactNotFoundException();
        }

        $content = new ReplyContent($input->content());

        try {
            $this->emailService->sendReplyToUser(
                $contact->email(),
                $content,
            );
        } catch (Throwable $e) {
            $reply = $this->replyContactFactory->create(
                $contact->contactIdentifier(),
                $input->identityIdentifier(),
                $contact->email(),
                $content,
                ReplyStatus::FAILED,
                null,
            );
            $this->replyContactRepository->save($reply);

            throw new FailedToSendEmailException($e->getMessage());
        }

        $reply = $this->replyContactFactory->create(
            $contact->contactIdentifier(),
            $input->identityIdentifier(),
            $contact->email(),
            $content,
            ReplyStatus::SENT,
            new DateTimeImmutable('now'),
        );
        $this->replyContactRepository->save($reply);
    }
}
