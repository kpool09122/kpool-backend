<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\SubmitUpdatedMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class SubmitUpdatedMember implements SubmitUpdatedMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
    ) {
    }

    /**
     * @param SubmitUpdatedMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     */
    public function process(SubmitUpdatedMemberInputPort $input): DraftMember
    {
        $member = $this->memberRepository->findDraftById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        if ($member->status() !== ApprovalStatus::Pending
        && $member->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $member->setStatus(ApprovalStatus::UnderReview);

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
