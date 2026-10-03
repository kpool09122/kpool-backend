<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class DeleteAccountData implements DeleteAccountDataInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
        private OfficialCertificationRepositoryInterface $officialCertificationRepository,
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    public function process(DeleteAccountDataInputPort $input, DeleteAccountDataOutputPort $output): void
    {
        $accountIdentifier = $input->accountIdentifier();
        $this->roleRepository->deleteByAccountIdentifier($accountIdentifier);
        $this->policyRepository->deleteByAccountIdentifier($accountIdentifier);
        $this->officialCertificationRepository->deleteByOwnerAccountIdentifier($accountIdentifier);
        foreach ($this->wikiRepository->findByOwnerAccountIdentifier($accountIdentifier) as $wiki) {
            $wiki->unmarkOfficial($accountIdentifier);
            $this->wikiRepository->save($wiki);
        }
    }
}
