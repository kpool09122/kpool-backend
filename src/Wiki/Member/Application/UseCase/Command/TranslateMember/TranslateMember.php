<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Application\Service\MemberServiceInterface;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;

class TranslateMember implements TranslateMemberInterface
{
    public function __construct(
        private MemberRepositoryInterface $memberRepository,
        private MemberServiceInterface    $memberService,
    ) {
    }

    /**
     * @param TranslateMemberInputPort $input
     * @return DraftMember[]
     * @throws MemberNotFoundException
     */
    public function process(TranslateMemberInputPort $input): array
    {
        $member = $this->memberRepository->findById($input->memberIdentifier());

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        $translations = Translation::allExcept($member->translation());

        $memberDrafts = [];
        foreach ($translations as $translation) {
            $memberDraft = $this->memberService->translateMember($member, $translation);
            $memberDrafts[] = $memberDraft;
            $this->memberRepository->saveDraft($memberDraft);
        }

        return $memberDrafts;
    }
}
