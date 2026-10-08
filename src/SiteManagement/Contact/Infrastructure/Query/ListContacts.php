<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutputPort;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class ListContacts implements ListContactsInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(ListContactsInputPort $input, ListContactsOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new UnauthorizedException();
        }

        /** @var LengthAwarePaginator<int, ContactModel> $paginator */
        $paginator = ContactModel::query()
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
            ->paginate($input->perPage(), ['*'], 'page', $input->page());

        foreach ($paginator->items() as $contact) {
            $owner = $contact->identity_identifier === null ? null : new IdentityIdentifier($contact->identity_identifier);
            if (! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $owner))) {
                throw new UnauthorizedException();
            }
        }

        $contacts = array_map(static fn (ContactModel $contact): ContactReadModel => new ContactReadModel(
            contactIdentifier: (string) $contact->id,
            identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
            category: (int) $contact->category,
            name: (string) $contact->name,
            replyIdentifiers: $contact->replies->map(static fn (ContactReply $reply): string => $reply->id)->values()->all(),
            createdAt: ($contact->created_at ?? throw new UnexpectedValueException('Persisted creation timestamp is missing.'))->format(DateTimeInterface::ATOM),
        ), $paginator->items());

        $output->output(
            $contacts,
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }
}
