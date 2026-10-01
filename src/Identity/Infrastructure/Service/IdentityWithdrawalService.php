<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Application\Models\Identity\Identity;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Throwable;

readonly class IdentityWithdrawalService implements IdentityWithdrawalServiceInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private ImageServiceInterface $imageService,
        private AuthContextCache $authContextCache,
        private LoggerInterface $logger,
    ) {
    }

    public function archive(IdentityIdentifier $identityIdentifier, DateTimeImmutable $archivedAt): void
    {
        $identity = Identity::query()->whereKey((string) $identityIdentifier)->lockForUpdate()->first();
        if ($identity === null) {
            throw new IdentityNotFoundException();
        }
        DB::table('archived_identities')->insert([
            'identity_id' => $identity->id,
            'language' => $identity->language,
            'identity_created_at' => $identity->created_at,
            'archived_at' => $archivedAt,
        ]);
    }

    public function delete(IdentityIdentifier $identityIdentifier): void
    {
        $identity = $this->identityRepository->findById($identityIdentifier) ?? throw new IdentityNotFoundException();
        $profileImage = $identity->profileImage();
        $this->identityRepository->delete($identityIdentifier);
        DB::afterCommit(function () use ($identityIdentifier, $profileImage): void {
            $this->authContextCache->forgetActor($identityIdentifier);
            $this->authContextCache->forgetAccount($identityIdentifier);
            $this->authContextCache->forgetWiki($identityIdentifier);
            $this->deleteProfileImage($profileImage);
        });
    }

    private function deleteProfileImage(?ImagePath $profileImage): void
    {
        if ($profileImage === null) {
            return;
        }

        try {
            if (! $this->imageService->delete($profileImage)) {
                $this->logger->warning('Withdrawn identity profile image cleanup failed.');
            }
        } catch (Throwable $exception) {
            $this->logger->warning('Withdrawn identity profile image cleanup failed.', ['exception' => $exception]);
        }
    }
}
