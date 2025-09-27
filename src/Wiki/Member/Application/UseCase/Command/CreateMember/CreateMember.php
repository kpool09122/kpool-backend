<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\CreateMember;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\Factory\DraftMemberFactoryInterface;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;

class CreateMember implements CreateMemberInterface
{
    public function __construct(
        private DraftMemberFactoryInterface $memberFactory,
        private MemberRepositoryInterface $memberRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param CreateMemberInputPort $input
     * @return DraftMember
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function process(CreateMemberInputPort $input): DraftMember
    {
        $member = $this->memberFactory->create(
            $input->editorIdentifier(),
            $input->translation(),
            $input->name(),
        );
        if ($input->publishedMemberIdentifier()) {
            $publishedMember = $this->memberRepository->findById($input->publishedMemberIdentifier());
            if ($publishedMember) {
                $member->setPublishedMemberIdentifier($publishedMember->memberIdentifier());
            }
        }
        $member->setRealName($input->realName());
        $member->setGroupIdentifiers($input->groupIdentifiers());
        $member->setBirthday($input->birthday());
        $member->setCareer($input->career());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $member->setImageLink($imageLink);
        }
        $member->setRelevantVideoLinks($input->relevantVideoLinks());

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
