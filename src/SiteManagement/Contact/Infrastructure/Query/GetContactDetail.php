<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutputPort;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class GetContactDetail implements GetContactDetailInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(GetContactDetailInputPort $input, GetContactDetailOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new UnauthorizedException();
        }

        $targetPrincipalIdentifier = $input->targetPrincipalIdentifier();
        if ($targetPrincipalIdentifier !== null) {
            // Preserve the principal-scoped API's authorization before looking up its contact.
            if (! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $targetPrincipalIdentifier))) {
                throw new UnauthorizedException();
            }
        } else {
            $operatorRole = $this->roleRepository->findSystemByName('Operator');
            if ($operatorRole === null || ! array_any(
                $this->principalGroupRepository->findByPrincipalId($principal->principalIdentifier()),
                static fn (PrincipalGroup $group): bool => (string) $group->accountIdentifier() === (string) $principal->accountIdentifier()
                    && $group->hasMember($principal->principalIdentifier())
                    && $group->hasRole($operatorRole->roleIdentifier()),
            )) {
                throw new UnauthorizedException();
            }
        }

        $contact = ContactModel::query()
            ->select(['id', 'principal_identifier', 'category', 'name', 'content', 'created_at'])
            ->where('id', (string) $input->contactIdentifier())
            ->when($targetPrincipalIdentifier !== null, static fn ($query) => $query->where('principal_identifier', (string) $targetPrincipalIdentifier))
            ->first();
        if ($targetPrincipalIdentifier === null) {
            $owner = $contact?->principal_identifier === null ? null : new PrincipalIdentifier($contact->principal_identifier);
            if (! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $owner))) {
                throw new UnauthorizedException();
            }
        }
        if ($contact === null) {
            throw new ContactNotFoundException();
        }

        $contact->load([
            'replies' => static function (Relation $query): void {
                $query->select(['id', 'contact_id', 'content', 'sent_at'])
                    ->whereNotNull('sent_at')
                    ->whereNull('failed_at')
                    ->orderBy('created_at')
                    ->orderBy('id');
            },
        ]);

        $replies = $contact->replies
            ->map(static fn (ContactReplyModel $reply): array => [
                'replyIdentifier' => (string) $reply->id,
                'content' => (string) $reply->content,
                'sentAt' => ($reply->sent_at ?? throw new UnexpectedValueException('Sent reply timestamp is missing.'))
                    ->format(DateTimeInterface::ATOM),
            ])
            ->all();

        $output->output(new ContactDetailReadModel(
            (string) $contact->id,
            $contact->principal_identifier === null ? null : (string) $contact->principal_identifier,
            (int) $contact->category,
            (string) $contact->name,
            ($contact->created_at ?? throw new UnexpectedValueException('Persisted creation timestamp is missing.'))
                ->format(DateTimeInterface::ATOM),
            (string) $contact->content,
            $replies,
        ));
    }
}
