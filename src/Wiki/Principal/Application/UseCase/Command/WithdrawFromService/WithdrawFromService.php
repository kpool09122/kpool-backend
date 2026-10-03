<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\WithdrawFromService;

use Source\Shared\Domain\Factory\ArchivedPrincipalFactoryInterface;
use Source\Shared\Domain\Repository\ArchivedPrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Wiki\Principal\Application\Service\WikiContextServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class WithdrawFromService implements WithdrawFromServiceInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private ArchivedPrincipalRepositoryInterface $archivedPrincipalRepository,
        private ArchivedPrincipalFactoryInterface $archivedPrincipalFactory,
        private WikiContextServiceInterface $wikiContextService,
    ) {
    }

    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void
    {
        $identityIdentifier = $input->identityIdentifier();
        $principals = $this->principalRepository->findByIdentityIdentifier($identityIdentifier);
        foreach ($principals as $principal) {
            $archivedPrincipal = $this->archivedPrincipalFactory->create(
                $identityIdentifier,
                ArchivedPrincipalType::WIKI,
                (string) $principal->principalIdentifier(),
                $principal->accountIdentifier(),
            );
            $this->archivedPrincipalRepository->save($archivedPrincipal);
        }
        $this->principalRepository->deleteByIdentityIdentifier($identityIdentifier);
        $this->wikiContextService->forget($identityIdentifier);
    }
}
