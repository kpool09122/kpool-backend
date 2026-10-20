<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact;

use DateTimeImmutable;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Exception\ContactNotRepliedByOperatorException;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyType;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Throwable;
use UnexpectedValueException;

readonly class ReplyMyContact implements ReplyMyContactInterface
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

    public function process(ReplyMyContactInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || $principal->status() !== PrincipalStatus::ACTIVE
            || ! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $principal->principalIdentifier()))) {
            throw new UnauthorizedException();
        }

        $contact = $this->contactRepository->findById($input->contactIdentifier());
        if ($contact === null) {
            throw new ContactNotFoundException();
        }
        if ($contact->principalIdentifier() === null || (string) $contact->principalIdentifier() !== (string) $principal->principalIdentifier()) {
            throw new UnauthorizedException();
        }

        if (! $this->replyContactRepository->hasSuccessfulOperatorReply($contact->contactIdentifier())) {
            throw new ContactNotRepliedByOperatorException();
        }

        $content = new ReplyContent($input->content());
        $reply = $this->replyContactFactory->create(
            $contact->contactIdentifier(),
            $principal->principalIdentifier(),
            $this->contactEmailService->administratorEmail(),
            $content,
            null,
            null,
            ReplyType::SUBMITTER,
        );
        $this->replyContactRepository->save($reply);

        try {
            $this->contactEmailService->sendReplyToAdministrator($contact, $content);
        } catch (Throwable $e) {
            $persisted = $this->replyContactRepository->findById($reply->replyIdentifier())
                ?? throw new UnexpectedValueException('Saved contact reply is missing.');
            $this->replyContactRepository->save(new ReplyCotact(
                $persisted->replyIdentifier(),
                $persisted->contactIdentifier(),
                $persisted->principalIdentifier(),
                $persisted->toEmail(),
                $persisted->content(),
                null,
                new DateTimeImmutable('now'),
                $persisted->createdAt(),
                $persisted->replyType(),
            ));

            throw new FailedToSendEmailException($e->getMessage());
        }

        $persisted = $this->replyContactRepository->findById($reply->replyIdentifier())
            ?? throw new UnexpectedValueException('Saved contact reply is missing.');
        $this->replyContactRepository->save(new ReplyCotact(
            $persisted->replyIdentifier(),
            $persisted->contactIdentifier(),
            $persisted->principalIdentifier(),
            $persisted->toEmail(),
            $persisted->content(),
            new DateTimeImmutable('now'),
            null,
            $persisted->createdAt(),
            $persisted->replyType(),
        ));
    }
}
