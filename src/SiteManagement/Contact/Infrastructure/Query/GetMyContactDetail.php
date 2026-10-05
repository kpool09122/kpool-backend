<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use DateTimeInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailOutputPort;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class GetMyContactDetail implements GetMyContactDetailInterface
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(GetMyContactDetailInputPort $input, GetMyContactDetailOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $principal->identityIdentifier()))) {
            throw new UnauthorizedException();
        }

        $contact = ContactModel::query()->select(['id', 'identity_identifier', 'category', 'name', 'content', 'created_at'])
            ->where('id', (string) $input->contactIdentifier())->where('identity_identifier', (string) $principal->identityIdentifier())->first();
        if ($contact === null) {
            throw new ContactNotFoundException();
        }
        $replies = ContactReplyModel::query()->select(['id', 'content', 'sent_at'])->where('contact_id', $contact->id)->whereNotNull('sent_at')->whereNull('failed_at')->orderBy('created_at')->orderBy('id')->get()
            ->map(static fn (ContactReplyModel $reply): array => ['replyIdentifier' => (string) $reply->id, 'content' => (string) $reply->content, 'sentAt' => ($reply->sent_at ?? throw new UnexpectedValueException('Sent reply timestamp is missing.'))->format(DateTimeInterface::ATOM)])->all();
        $output->output(new ContactDetailReadModel((string) $contact->id, (string) $contact->identity_identifier, (int) $contact->category, (string) $contact->name, ($contact->created_at ?? throw new UnexpectedValueException('Persisted creation timestamp is missing.'))->format(DateTimeInterface::ATOM), (string) $contact->content, $replies));
    }
}
