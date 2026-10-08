<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use DateTimeImmutable;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Throwable;
use UnexpectedValueException;

readonly class ReplyContact implements ReplyContactInterface
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private ReplyContactFactoryInterface $replyContactFactory,
        private ReplyContactRepositoryInterface $replyContactRepository,
        private ContactEmailServiceInterface $contactEmailService,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws ContactNotFoundException
     * @throws FailedToSendEmailException
     * @throws UnauthorizedException
     */
    public function process(ReplyContactInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::CONTACT_REPLY, new Resource(ResourceType::CONTACT))) {
            throw new UnauthorizedException();
        }

        $contact = $this->contactRepository->findById($input->contactIdentifier());
        if ($contact === null) {
            throw new ContactNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate($principal, Action::CONTACT_REPLY, new Resource(ResourceType::CONTACT, $contact->principalIdentifier()))) {
            throw new UnauthorizedException();
        }

        $content = new ReplyContent($input->content());

        $reply = $this->replyContactFactory->create(
            $contact->contactIdentifier(),
            $principal->principalIdentifier(),
            $contact->email(),
            $content,
            null,
            null,
        );
        $this->replyContactRepository->save($reply);

        try {
            $this->contactEmailService->sendReplyToUser(
                $contact,
                $content,
            );
        } catch (Throwable $e) {
            $persisted = $this->replyContactRepository->findById($reply->replyIdentifier())
                ?? throw new UnexpectedValueException('Saved contact reply is missing.');
            $failed = new ReplyCotact(
                $persisted->replyIdentifier(),
                $persisted->contactIdentifier(),
                $persisted->principalIdentifier(),
                $persisted->toEmail(),
                $persisted->content(),
                null,
                new DateTimeImmutable('now'),
                $persisted->createdAt(),
            );
            $this->replyContactRepository->save($failed);

            throw new FailedToSendEmailException($e->getMessage());
        }

        // findById で取得してから送信完了日時を更新
        $persisted = $this->replyContactRepository->findById($reply->replyIdentifier())
                ?? throw new UnexpectedValueException('Saved contact reply is missing.');
        $sentAt = new DateTimeImmutable('now');
        $sent = new ReplyCotact(
            $persisted->replyIdentifier(),
            $persisted->contactIdentifier(),
            $persisted->principalIdentifier(),
            $persisted->toEmail(),
            $persisted->content(),
            $sentAt,
            null,
            $persisted->createdAt(),
        );
        $this->replyContactRepository->save($sent);
    }
}
