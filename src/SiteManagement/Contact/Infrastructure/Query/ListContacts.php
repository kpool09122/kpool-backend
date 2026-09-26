<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use DateTimeInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutputPort;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

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

        $query = ContactModel::query()
            ->select([
                'contacts.id',
                'contacts.identity_identifier',
                'contacts.category',
                'contacts.name',
                'contacts.created_at',
                'contact_replies.id as reply_identifier',
            ])
            ->leftJoin('contact_replies', static function (JoinClause $join): void {
                $join->on('contact_replies.contact_id', '=', 'contacts.id')
                    ->whereNotNull('contact_replies.sent_at')
                    ->whereNull('contact_replies.failed_at');
            })
            ->orderByDesc('contacts.created_at')
            ->orderByDesc('contacts.id')
            ->orderBy('contact_replies.created_at')
            ->orderBy('contact_replies.id');

        if ($input->targetIdentityIdentifier() !== null) {
            $query->where('contacts.identity_identifier', (string) $input->targetIdentityIdentifier());
        }

        if ($input->hasReply() === true) {
            $query->whereNotNull('contact_replies.id');
        } elseif ($input->hasReply() === false) {
            $query->whereNull('contact_replies.id');
        }

        $contacts = $query
            ->get()
            ->groupBy('id')
            ->map(static function (Collection $contactRows): ContactReadModel {
                /** @var ContactModel $contact */
                $contact = $contactRows->first();

                return new ContactReadModel(
                    contactIdentifier: (string) $contact->id,
                    identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
                    category: (int) $contact->category,
                    name: (string) $contact->name,
                    replyIdentifiers: $contactRows->pluck('reply_identifier')->filter()->map(static fn (mixed $identifier): string => (string) $identifier)->values()->all(),
                    createdAt: $contact->created_at->format(DateTimeInterface::ATOM),
                );
            })
            ->all();

        $output->output($contacts);
    }
}
