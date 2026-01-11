<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class CreatePrincipal implements CreatePrincipalInterface
{
    private const DEFAULT_PRINCIPAL_GROUP_NAME = 'Default';

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
    ) {
    }

    /**
     * @param CreatePrincipalInputPort $input
     * @return Principal
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input): Principal
    {
        $existingPrincipal = $this->principalRepository->findByIdentityIdentifier(
            $input->identityIdentifier()
        );

        if ($existingPrincipal !== null) {
            throw new PrincipalAlreadyExistsException();
        }

        $principal = $this->principalFactory->create(
            $input->identityIdentifier(),
        );

        $this->principalRepository->save($principal);

        // Default PrincipalGroup の取得または作成
        $defaultPrincipalGroup = $this->principalGroupRepository->findDefaultByAccountId(
            $input->accountIdentifier()
        );

        if ($defaultPrincipalGroup === null) {
            $defaultPrincipalGroup = $this->principalGroupFactory->create(
                $input->accountIdentifier(),
                self::DEFAULT_PRINCIPAL_GROUP_NAME,
                true,
            );
            $this->principalGroupRepository->save($defaultPrincipalGroup);
        }

        // Principal を Default PrincipalGroup に追加
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($defaultPrincipalGroup);

        return $principal;
    }
}
