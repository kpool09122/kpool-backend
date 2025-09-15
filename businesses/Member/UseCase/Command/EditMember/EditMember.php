<?php

namespace Businesses\Member\UseCase\Command\EditMember;

use Businesses\Member\Domain\Entity\Member;
use Businesses\Member\Domain\Repository\MemberRepositoryInterface;
use Businesses\Member\UseCase\Exception\MemberNotFoundException;
use Businesses\Shared\Service\ImageServiceInterface;

readonly class EditMember implements EditMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param EditMemberInputPort $input
     * @return ?Member
     * @throws MemberNotFoundException
     */
    public function process(EditMemberInputPort $input): ?Member
    {
        $member = $this->memberRepository->findById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }



        $member->setName($input->name());
        $member->setGroupIdentifier($input->groupIdentifier());
        $member->setBirthday($input->birthday());
        $member->setCareer($input->career());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $member->setImageLink($imageLink);
        }

        $this->memberRepository->save($member);

        return $this->memberRepository->findById($input->memberIdentifier());
    }
}
