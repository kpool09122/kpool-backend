<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalOutputPort;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class ListContactsByPrincipal implements ListContactsByPrincipalInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(ListContactsByPrincipalInputPort $input, ListContactsByPrincipalOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new UnauthorizedException();
        }

        $contacts = ContactModel::query()
            ->select(['id', 'principal_identifier', 'category', 'name', 'created_at'])
            ->where('principal_identifier', (string) $input->targetPrincipalIdentifier())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $replyIdentifiersByContactIdentifier = ContactReplyModel::query()
            ->select(['contact_id', 'id'])
            ->whereIn('contact_id', $contacts->pluck('id'))
            ->whereNotNull('sent_at')
            ->whereNull('failed_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('contact_id')
            ->map(static fn (Collection $replies): array => $replies->map(static fn (ContactReplyModel $reply): string => $reply->id)->values()->all())
            ->all();

        foreach ($contacts as $contact) {
            $owner = $contact->principal_identifier === null ? null : new PrincipalIdentifier((string) $contact->principal_identifier);
            if (! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $owner))) {
                throw new UnauthorizedException();
            }
        }

        $contacts = $contacts
            ->map(fn (ContactModel $contact): ContactReadModel => new ContactReadModel(
                contactIdentifier: (string) $contact->id,
                principalIdentifier: $contact->principal_identifier === null ? null : (string) $contact->principal_identifier,
                category: (int) $contact->category,
                name: (string) $contact->name,
                replyIdentifiers: $replyIdentifiersByContactIdentifier[(string) $contact->id] ?? [],
                createdAt: ($contact->created_at ?? throw new UnexpectedValueException('Persisted creation timestamp is missing.'))->format(DateTimeInterface::ATOM),
            ))
            ->all();

        $output->output($contacts);
    }
}
