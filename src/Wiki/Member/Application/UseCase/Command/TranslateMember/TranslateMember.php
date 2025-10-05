<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Application\Service\TranslationServiceInterface;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class TranslateMember implements TranslateMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private TranslationServiceInterface $translationService,
    ) {
    }

    /**
     * @param TranslateMemberInputPort $input
     * @return DraftMember[]
     * @throws MemberNotFoundException
     * @throws UnauthorizedException
     */
    public function process(TranslateMemberInputPort $input): array
    {
        $member = $this->memberRepository->findById($input->memberIdentifier());

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

        if (! $principal->role()->can(Action::TRANSLATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $translations = Translation::allExcept($member->translation());

        $memberDrafts = [];
        foreach ($translations as $translation) {
            // 外部翻訳サービスを使って翻訳
            $memberDraft = $this->translationService->translateMember($member, $translation);
            $memberDrafts[] = $memberDraft;
            $this->memberRepository->saveDraft($memberDraft);
        }

        return $memberDrafts;
    }
}
