<?php

namespace Businesses\Wiki\Member\UseCase\Command\EditMember;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Businesses\Wiki\Member\UseCase\Exception\MemberNotFoundException;

readonly class EditMember implements EditMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param EditMemberInputPort $input
     * @return Member
     * @throws MemberNotFoundException
     */
    public function process(EditMemberInputPort $input): Member
    {
        $member = $this->memberRepository->findById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        $member->setName($input->name());
        $member->setGroupIdentifiers($input->groupIdentifiers());
        $member->setBirthday($input->birthday());
        $member->setCareer($input->career());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $member->setImageLink($imageLink);
        }
        $member->setRelevantVideoLinks($input->relevantVideoLinks());

        $this->memberRepository->save($member);

        return $member;
    }
}
