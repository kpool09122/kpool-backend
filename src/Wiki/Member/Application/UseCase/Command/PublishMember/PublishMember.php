<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Application\Exception\ExistsApprovedButNotTranslatedMemberException;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\Factory\MemberFactoryInterface;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Member\Domain\Service\MemberServiceInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class PublishMember implements PublishMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private MemberServiceInterface $memberService,
        private MemberFactoryInterface    $memberFactory,
    ) {
    }

    /**
     * @param PublishMemberInputPort $input
     * @return Member
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedMemberException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function process(PublishMemberInputPort $input): Member
    {
        $member = $this->memberRepository->findDraftById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
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

        if ($member->publishedMemberIdentifier()) {
            $publishedMember = $this->memberRepository->findById($input->publishedMemberIdentifier());
            if ($publishedMember === null) {
                throw new MemberNotFoundException();
            }
            $publishedMember->setName($member->name());
        } else {
            $publishedMember = $this->memberFactory->create(
                $member->translationSetIdentifier(),
                $member->translation(),
                $member->name(),
            );
        }
        $publishedMember->setRealName($member->realName());
        $publishedMember->setGroupIdentifiers($member->groupIdentifiers());
        $publishedMember->setBirthday($member->birthday());
        $publishedMember->setCareer($member->career());
        $publishedMember->setImageLink($member->imageLink());
        $publishedMember->setRelevantVideoLinks($member->relevantVideoLinks());

        $this->memberRepository->save($publishedMember);
        $this->memberRepository->deleteDraft($member);

        return $publishedMember;
    }
}
