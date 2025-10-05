<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class RejectMember implements RejectMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
    ) {
    }

    /**
     * @param RejectMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(RejectMemberInputPort $input): DraftMember
    {
        $member = $this->memberRepository->findDraftById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        $principal = $input->principal();
        $groupIds = array_map(
            fn ($groupIdentifier) => (string) $groupIdentifier,
            $member->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::MEMBER,
            agencyId: null,
            groupIds: $groupIds,
            memberId: (string) $member->memberIdentifier(),
        );

        if (! $principal->role()->can(Action::REJECT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($member->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $member->setStatus(ApprovalStatus::Rejected);

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
