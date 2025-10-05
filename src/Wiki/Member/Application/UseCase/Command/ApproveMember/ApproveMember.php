<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\ApproveMember;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Member\Application\Exception\ExistsApprovedButNotTranslatedMemberException;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Member\Domain\Service\MemberServiceInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ApproveMember implements ApproveMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private MemberServiceInterface $memberService,
    ) {
    }

    /**
     * @param ApproveMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(ApproveMemberInputPort $input): DraftMember
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
        // FIXME: agencyId も取得できるようにリファクタリングする
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::MEMBER,
            agencyId: null,
            groupIds: $groupIds,
            memberId: (string) $member->memberIdentifier(),
        );

        if (! $principal->role()->can(Action::APPROVE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($member->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->memberService->existsApprovedButNotTranslatedMember(
            $member->translationSetIdentifier(),
            $member->memberIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedMemberException();
        }

        $member->setStatus(ApprovalStatus::Approved);

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
