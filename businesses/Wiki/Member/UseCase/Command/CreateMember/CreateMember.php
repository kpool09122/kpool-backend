<?php

namespace Businesses\Wiki\Member\UseCase\Command\CreateMember;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Businesses\Wiki\Member\Domain\Factory\MemberFactoryInterface;
use Businesses\Wiki\Member\Domain\Repository\MemberRepositoryInterface;

class CreateMember implements CreateMemberInterface
{
    public function __construct(
        private MemberFactoryInterface $memberFactory,
        private MemberRepositoryInterface $memberRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param CreateMemberInputPort $input
     * @return Member
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function process(CreateMemberInputPort $input): Member
    {
        $member = $this->memberFactory->create($input->translation(), $input->name());
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
