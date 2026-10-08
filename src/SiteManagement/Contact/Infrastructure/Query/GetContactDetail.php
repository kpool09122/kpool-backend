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
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class GetContactDetail implements GetContactDetailInterface
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(GetContactDetailInputPort $input, GetContactDetailOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $input->targetPrincipalIdentifier()))) {
            throw new UnauthorizedException();
        }

        $contact = ContactModel::query()
            ->select(['id', 'principal_identifier', 'category', 'name', 'content', 'created_at'])
            ->with([
                'replies' => static function (Relation $query): void {
                    $query->select(['id', 'contact_id', 'content', 'sent_at'])
                        ->whereNotNull('sent_at')
                        ->whereNull('failed_at')
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ])
            ->where('id', (string) $input->contactIdentifier())
            ->where('principal_identifier', (string) $input->targetPrincipalIdentifier())
            ->first();
        if ($contact === null) {
            throw new ContactNotFoundException();
        }

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
            (string) $contact->principal_identifier,
            (int) $contact->category,
            (string) $contact->name,
            ($contact->created_at ?? throw new UnexpectedValueException('Persisted creation timestamp is missing.'))
                ->format(DateTimeInterface::ATOM),
            (string) $contact->content,
            $replies,
        ));
    }
}
