<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Monetization\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Monetization\Principal\Infrastructure\Repository\RoleRepository;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\MonetizationPrincipalTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationUniquenessTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function associations(): iterable
    {
        yield 'membership' => ['monetization_principal_group_memberships'];
        yield 'group role' => ['monetization_principal_group_role_attachments'];
        yield 'role policy' => ['monetization_role_policy_attachments'];
    }

    #[DataProvider('associations')]
    public function testDuplicateAssociationsAreRejectedByDatabase(string $table): void
    {
        $data = MonetizationPrincipalTestData::create();
        CreateAccount::create((string) $data->principal->accountIdentifier());
        CreateIdentity::create($data->principal->identityIdentifier());
        (new PrincipalRepository())->save($data->principal);
        $policyId = new PolicyIdentifier(StrTestHelper::generateUuid());
        (new PolicyRepository())->save(new Policy($policyId, 'read', [], null));
        (new RoleRepository())->save(new Role($data->role->roleIdentifier(), 'reader', [$policyId], null));
        (new PrincipalGroupRepository())->save(new PrincipalGroup($data->group->principalGroupIdentifier(), 'readers', [$data->role->roleIdentifier()], $data->principal->accountIdentifier(), true, null, [$data->principal->principalIdentifier()]));
        $record = match ($table) {
            'monetization_principal_group_memberships' => ['principal_group_id' => (string) $data->group->principalGroupIdentifier(), 'principal_id' => (string) $data->principal->principalIdentifier()],
            'monetization_principal_group_role_attachments' => ['principal_group_id' => (string) $data->group->principalGroupIdentifier(), 'role_id' => (string) $data->role->roleIdentifier()],
            default => ['role_id' => (string) $data->role->roleIdentifier(), 'policy_id' => (string) $policyId],
        };
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table($table)->insert($record);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function names(): iterable
    {
        yield 'group account' => ['group', false];
        yield 'role account' => ['role', false];
        yield 'role system' => ['role', true];
        yield 'policy account' => ['policy', false];
        yield 'policy system' => ['policy', true];
    }

    #[DataProvider('names')]
    public function testNamesAreUniqueWithinTheirScope(string $kind, bool $system): void
    {
        $data = MonetizationPrincipalTestData::create();
        $account = $data->principal->accountIdentifier();
        CreateAccount::create((string) $account);
        $scope = $system ? null : $account;
        if ($kind === 'group') {
            $repository = new PrincipalGroupRepository();
            $repository->save(new PrincipalGroup($data->group->principalGroupIdentifier(), 'same', [], $account, false, null, []));
            $this->expectException(UniqueConstraintViolationException::class);
            $repository->save(new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'same', [], $account, false, null, []));
        } elseif ($kind === 'role') {
            $repository = new RoleRepository();
            $repository->save(new Role($data->role->roleIdentifier(), 'same', [], $scope));
            $this->expectException(UniqueConstraintViolationException::class);
            $repository->save(new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'same', [], $scope));
        } else {
            $repository = new PolicyRepository();
            $repository->save(new Policy(new PolicyIdentifier(StrTestHelper::generateUuid()), 'same', [], $scope));
            $this->expectException(UniqueConstraintViolationException::class);
            $repository->save(new Policy(new PolicyIdentifier(StrTestHelper::generateUuid()), 'same', [], $scope));
        }
    }
}
