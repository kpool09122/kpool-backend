<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use DateTimeInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class GetContactDetail implements GetContactDetailInterface
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function process(GetContactDetailInput $input, GetContactDetailOutput $output): void
    {
        if (! $this->userRepository->findByIdentityIdentifier($input->requesterIdentityIdentifier())?->isAdmin()) {
            throw new UnauthorizedException();
        }
        $contact = ContactModel::query()->select(['id', 'identity_identifier', 'category', 'name', 'content', 'created_at'])->where('id', (string) $input->contactIdentifier())->where('identity_identifier', (string) $input->targetIdentityIdentifier())->first();
        if ($contact === null) {
            throw new ContactNotFoundException();
        }
        $replies = ContactReplyModel::query()->select(['id', 'content', 'sent_at'])->where('contact_id', $contact->id)->whereNotNull('sent_at')->whereNull('failed_at')->orderBy('created_at')->orderBy('id')->get()
            ->map(static fn (ContactReplyModel $reply): array => ['replyIdentifier' => (string) $reply->id, 'content' => (string) $reply->content, 'sentAt' => $reply->sent_at->format(DateTimeInterface::ATOM)])->all();
        $output->output(new ContactDetailReadModel((string) $contact->id, (string) $contact->identity_identifier, (int) $contact->category, (string) $contact->name, $contact->created_at->format(DateTimeInterface::ATOM), (string) $contact->content, $replies));
    }
}
