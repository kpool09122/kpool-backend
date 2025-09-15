<?php

namespace Businesses\Member\UseCase\Command\CreateMember;

use Businesses\Member\Domain\Entity\Member;
use Businesses\Member\Domain\Factory\MemberFactoryInterface;
use Businesses\Member\Domain\Repository\MemberRepositoryInterface;
use Businesses\Shared\Service\ImageServiceInterface;

class CreateMember implements CreateMemberInterface
{
    public function __construct(
        private MemberFactoryInterface    $memberFactory,
        private MemberRepositoryInterface $memberRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    public function process(CreateMemberInputPort $input): ?Member
    {
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
        }

        $member = $this->memberFactory->create(
            $input->name(),
            $input->groupIdentifier(),
            $input->birthday(),
            $input->career(),
            $imageLink ?? null,
        );

        $this->memberRepository->save($member);

        return $this->memberRepository->findById($member->memberIdentifier());
    }
}
