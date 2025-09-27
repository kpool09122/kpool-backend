<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\ApproveUpdatedMember;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Member\Application\Exception\ExistsApprovedButNotTranslatedMemberException;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Application\Service\MemberServiceInterface;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ApproveUpdatedMember implements ApproveUpdatedMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private MemberServiceInterface    $memberService,
    ) {
    }

    /**
     * @param ApproveUpdatedMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     */
    public function process(ApproveUpdatedMemberInputPort $input): DraftMember
    {
        $member = $this->memberRepository->findDraftById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        if ($member->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }


        if ($input->publishedMemberIdentifier()) {
            if ($this->memberService->existsApprovedButNotTranslatedMember(
                $input->memberIdentifier(),
                $input->publishedMemberIdentifier(),
            )) {
                throw new ExistsApprovedButNotTranslatedMemberException();
            }
        }


        $member->setStatus(ApprovalStatus::Approved);

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
