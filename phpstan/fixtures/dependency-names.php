<?php

namespace DependencyNameFixture;

interface IdentityRepositoryInterface {}
interface AuthServiceInterface {}
class Example
{
    private IdentityRepositoryInterface $identities;
    private ?AuthServiceInterface $authService;
    public function __construct(
        private IdentityRepositoryInterface $repository,
        AuthServiceInterface $auth,
        ?IdentityRepositoryInterface $identityRepository,
        array $sessions,
        IdentityRepositoryInterface|null $repos,
    ) {}
}

use DependencyNameFixture\IdentityRepositoryInterface as AccountIdentityRepositoryInterface;
class AliasedDependency
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private AccountIdentityRepositoryInterface $accountIdentityRepository,
    ) {}
}
