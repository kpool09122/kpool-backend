<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\EditMember;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class EditMember implements EditMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param EditMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditMemberInputPort $input): DraftMember
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
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::MEMBER,
            agencyId: null,
            groupIds: $groupIds,
            memberId: (string) $member->memberIdentifier(),
        );

        if (! $principal->role()->can(Action::EDIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
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

        $this->memberRepository->saveDraft($member);

        return $member;
    }
}
