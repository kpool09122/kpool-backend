<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Models\Identity\Identity as IdentityModel;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Identity\Application\UseCase\Query\AuthenticatedIdentityReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Infrastructure\Support\ImageUrl;

readonly class GetAuthenticatedIdentity implements GetAuthenticatedIdentityInterface
{
    public function __construct(
        private AccountResolver $accountResolver,
        private AuthContextCache $cache,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     */
    public function process(GetAuthenticatedIdentityInputPort $input): AuthenticatedIdentityReadModel
    {
        $model = IdentityModel::query()
            ->where('id', (string) $input->identityIdentifier())
            ->first();

        if ($model === null) {
            throw new IdentityNotFoundException();
        }

        /** @var AccountContext|null $accountContext */
        $accountContext = null;

        try {
            $accountContext = $this->cache->resolveAccount(
                $input->identityIdentifier(),
                fn () => $this->accountResolver->resolve($input->identityIdentifier()),
            );
        } catch (AccountNotFoundException) {
            $accountContext = null;
        }

        return new AuthenticatedIdentityReadModel(
            identityIdentifier: $model->id,
            identityName: $model->identity_name,
            email: $model->email,
            language: $model->language,
            profileImage: ImageUrl::fromPath($model->profile_image),
            accountIdentifier: $accountContext === null ? null : (string) $accountContext->principal()->accountIdentifier(),
            accountRole: $accountContext === null ? null : $this->resolveAccountRole($accountContext)?->value,
        );
    }

    private function resolveAccountRole(AccountContext $accountContext): ?AccountRole
    {
        $principal = $accountContext->principal();
        $principalGroups = $this->principalGroupRepository->findByAccountIdAndPrincipal(
            $principal->accountIdentifier(),
            $principal->principalIdentifier(),
        );

        $roles = [];
        foreach ($principalGroups as $principalGroup) {
            $roles[$principalGroup->role()->value] = $principalGroup->role();
        }

        foreach ([AccountRole::OWNER, AccountRole::ADMIN, AccountRole::MEMBER] as $role) {
            if (isset($roles[$role->value])) {
                return $role;
            }
        }

        return array_values($roles)[0] ?? null;
    }
}
