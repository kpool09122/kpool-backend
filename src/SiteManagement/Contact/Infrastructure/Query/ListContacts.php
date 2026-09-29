<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutputPort;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;
use UnexpectedValueException;

readonly class ListContacts implements ListContactsInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function process(ListContactsInputPort $input, ListContactsOutputPort $output): void
    {
        $requester = $this->userRepository->findByIdentityIdentifier($input->requesterIdentityIdentifier());
        if (! $requester?->isAdmin()) {
            throw new UnauthorizedException();
        }

        $contacts = ContactModel::query()
            ->select([
                'id',
                'identity_identifier',
                'category',
                'name',
                'created_at',
            ])
            ->with([
                'replies' => static function (Relation $query): void {
                    $query->select(['id', 'contact_id'])
                        ->whereNotNull('sent_at')
                        ->whereNull('failed_at')
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ])
            ->when($input->targetIdentityIdentifier() !== null, fn ($query) => $query->where('identity_identifier', (string) $input->targetIdentityIdentifier()))
            ->when($input->hasReply() === true, fn ($query) => $query->whereHas('replies', static fn ($replyQuery) => $replyQuery
                ->whereNotNull('sent_at')
                ->whereNull('failed_at')))
            ->when($input->hasReply() === false, fn ($query) => $query->whereDoesntHave('replies', static fn ($replyQuery) => $replyQuery
                ->whereNotNull('sent_at')
                ->whereNull('failed_at')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (ContactModel $contact): ContactReadModel => new ContactReadModel(
                contactIdentifier: (string) $contact->id,
                identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
                category: (int) $contact->category,
                name: (string) $contact->name,
                replyIdentifiers: $contact->replies->map(static fn (ContactReply $reply): string => $reply->id)->values()->all(),
                createdAt: ($contact->created_at ?? throw new UnexpectedValueException('Persisted creation timestamp is missing.'))->format(DateTimeInterface::ATOM),
            ))
            ->all();

        $output->output($contacts);
    }
}
