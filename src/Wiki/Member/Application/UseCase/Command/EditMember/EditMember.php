<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\EditMember;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;

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
        $member->setRealName($input->realName());
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
