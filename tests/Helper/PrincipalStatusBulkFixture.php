<?php

declare(strict_types=1);

namespace Tests\Helper;

use Database\Seeders\AccountAuthorizationSeeder;
use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers as AccountMembers;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalStatusUpdate as AccountStatusUpdate;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers as AccountUpdate;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput as AccountInput;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput as AccountOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup as AccountGroup;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus as AccountStatus;
use Source\Account\Principal\Infrastructure\Repository\PrincipalGroupRepository as AccountGroupRepository;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier as AccountGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers as SiteMembers;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalStatusUpdate as SiteStatusUpdate;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers as SiteUpdate;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput as SiteInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput as SiteOutput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier as SiteGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier as SitePrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus as SiteStatus;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers as WikiMembers;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalStatusUpdate as WikiStatusUpdate;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers as WikiUpdate;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput as WikiInput;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput as WikiOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup as WikiGroup;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface as WikiRoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier as WikiGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus as WikiStatus;
use Source\Wiki\Principal\Infrastructure\Repository\PrincipalGroupRepository as WikiGroupRepository;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier as WikiPrincipalIdentifier;

class PrincipalStatusBulkFixture
{
    public SiteManagementPrincipalGroupsFixture $base;
    public string $manager;
    public string $successor;
    public string $forced;
    public string $foreign;
    public string $group;
    public string $prefix;

    public function __construct(public readonly string $context)
    {
        $this->base = new SiteManagementPrincipalGroupsFixture();
        $this->prefix = $context === 'site' ? 'site_management' : $context;
        $this->manager = (string) $this->base->administrator->principalIdentifier();
        $this->successor = (string) $this->base->otherAdministrator->principalIdentifier();
        $this->forced = (string) $this->base->unassigned->principalIdentifier();
        $this->foreign = (string) $this->base->foreign->principalIdentifier();
        if ($context === 'account') {
            app(AccountAuthorizationSeeder::class)->run();
            $repository = app(AccountPrincipalRepositoryInterface::class);
            $this->manager = (string) $repository->findByIdentityIdentifierAndAccountIdentifier($this->base->administrator->identityIdentifier(), $this->base->account)?->principalIdentifier();
            $this->successor = (string) $repository->findByIdentityIdentifierAndAccountIdentifier($this->base->otherAdministrator->identityIdentifier(), $this->base->account)?->principalIdentifier();
            $this->forced = (string) $repository->findByIdentityIdentifierAndAccountIdentifier($this->base->unassigned->identityIdentifier(), $this->base->account)?->principalIdentifier();
            $this->foreign = (string) $repository->findByIdentityIdentifierAndAccountIdentifier($this->base->foreign->identityIdentifier(), $this->base->otherAccount)?->principalIdentifier();
            $owner = app(AccountRoleRepositoryInterface::class)->findSystemByName('Owner');
            assert($owner !== null);
            $group = new AccountGroup(new AccountGroupIdentifier(StrTestHelper::generateUuid()), $this->base->account, 'Managers', false, new DateTimeImmutable(), [$owner->roleIdentifier()]);
            $group->replaceMembers([new AccountPrincipalIdentifier($this->manager)]);
            (new AccountGroupRepository())->save($group);
            $this->group = (string) $group->principalGroupIdentifier();
        } elseif ($context === 'wiki') {
            app(SystemPolicySeeder::class)->run();
            app(SystemRoleSeeder::class)->run();
            CreatePrincipal::create(new WikiPrincipalIdentifier($this->foreign), $this->base->foreign->identityIdentifier(), $this->base->otherAccount);
            foreach ([$this->base->administrator, $this->base->otherAdministrator, $this->base->unassigned] as $principal) {
                CreatePrincipal::create(new WikiPrincipalIdentifier((string) $principal->principalIdentifier()), $principal->identityIdentifier(), $this->base->account);
            }
            $role = app(WikiRoleRepositoryInterface::class)->findSystemByName('Administrator');
            assert($role !== null);
            $group = new WikiGroup(new WikiGroupIdentifier(StrTestHelper::generateUuid()), $this->base->account, 'Managers', false, new DateTimeImmutable());
            $group->addRole($role);
            $group->replaceMembers([new WikiPrincipalIdentifier($this->manager)]);
            (new WikiGroupRepository())->save($group);
            $this->group = (string) $group->principalGroupIdentifier();
        } else {
            $this->group = (string) $this->base->administrators->principalGroupIdentifier();
            DB::table('site_management_principal_group_memberships')->where('principal_group_id', $this->group)->where('principal_id', $this->successor)->delete();
        }
        DB::table($this->prefix . '_principals')->where('id', $this->successor)->update(['status' => 'suspended']);
        DB::table($this->prefix . '_principals')->where('id', $this->forced)->update(['status' => 'forced_stop']);
    }

    /** @param array<string, string> $statuses
     * @param list<string>|null $members
     * @return array{principalStatuses: array<int, array{principalIdentifier: string, status: string}>, principalGroups: array<int, array<string, mixed>>} */
    public function save(array $statuses, ?array $members = null): array
    {
        return $this->saveEntries(array_map(static fn (string $id, string $status): array => ['principalIdentifier' => $id, 'status' => $status], array_keys($statuses), array_values($statuses)), $members);
    }

    /** @param list<array{principalIdentifier: string, status: string}> $statuses
     * @param list<string>|null $members
     * @return array{principalStatuses: array<int, array{principalIdentifier: string, status: string}>, principalGroups: array<int, array<string, mixed>>} */
    public function saveEntries(array $statuses, ?array $members = null): array
    {
        return DB::transaction(function () use ($statuses, $members): array {
            if ($this->context === 'account') {
                $output = new AccountOutput();
                $principal = app(AccountPrincipalRepositoryInterface::class)->findById(new AccountPrincipalIdentifier($this->manager));
                assert($principal !== null);
                app(AccountUpdate::class)->process(new AccountInput(
                    $this->base->account,
                    $principal,
                    $members === null ? [] : [new AccountMembers(new AccountGroupIdentifier($this->group), array_map(static fn (string $id): AccountPrincipalIdentifier => new AccountPrincipalIdentifier($id), $members))],
                    principalStatuses: array_map(static fn (array $entry): AccountStatusUpdate => new AccountStatusUpdate(new AccountPrincipalIdentifier($entry['principalIdentifier']), AccountStatus::from($entry['status'])), $statuses)
                ), $output);

                return $output->toArray();
            }
            if ($this->context === 'wiki') {
                $output = new WikiOutput();
                app(WikiUpdate::class)->process(new WikiInput(
                    $this->base->account,
                    new WikiPrincipalIdentifier($this->manager),
                    $members === null ? [] : [new WikiMembers(new WikiGroupIdentifier($this->group), array_map(static fn (string $id): WikiPrincipalIdentifier => new WikiPrincipalIdentifier($id), $members))],
                    accountType: AccountType::INDIVIDUAL,
                    principalStatuses: array_map(static fn (array $entry): WikiStatusUpdate => new WikiStatusUpdate(new WikiPrincipalIdentifier($entry['principalIdentifier']), WikiStatus::from($entry['status'])), $statuses)
                ), $output);

                return $output->toArray();
            }
            $output = new SiteOutput();
            app(SiteUpdate::class)->process(new SiteInput(
                $this->base->account,
                new SitePrincipalIdentifier($this->manager),
                $members === null ? [] : [new SiteMembers(new SiteGroupIdentifier($this->group), array_map(static fn (string $id): SitePrincipalIdentifier => new SitePrincipalIdentifier($id), $members))],
                principalStatuses: array_map(static fn (array $entry): SiteStatusUpdate => new SiteStatusUpdate(new SitePrincipalIdentifier($entry['principalIdentifier']), SiteStatus::from($entry['status'])), $statuses)
            ), $output);

            return $output->toArray();
        });
    }
}
