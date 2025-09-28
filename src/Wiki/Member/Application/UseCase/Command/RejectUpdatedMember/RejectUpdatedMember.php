<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectUpdatedMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class RejectUpdatedMember implements RejectUpdatedMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
    ) {
    }

    /**
     * @param RejectUpdatedMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectUpdatedMemberInputPort $input): DraftMember
    {
        $member = $this->memberRepository->findDraftById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        if ($member->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $member->setStatus(ApprovalStatus::Rejected);

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
