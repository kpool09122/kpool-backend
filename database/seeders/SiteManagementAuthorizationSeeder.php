<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;

class SiteManagementAuthorizationSeeder extends Seeder
{
    public const string GENERAL_ROLE = '69200000-0000-7000-8000-000000000001';
    public const string ADMIN_ROLE = '69200000-0000-7000-8000-000000000002';

    public function run(): void
    {
        foreach ([self::GENERAL_ROLE => 'general', self::ADMIN_ROLE => 'administrator'] as $id => $name) {
            $admin = $id === self::ADMIN_ROLE;
            $statements = $admin ? [['effect' => 'allow', 'actions' => array_map(static fn (Action $a): string => $a->value, Action::cases()), 'resource_types' => ['announcement','contact'], 'condition' => null]] : [['effect' => 'allow','actions' => ['contact:view'],'resource_types' => ['contact'],'condition' => 'own_contact']];
            DB::table('site_management_policies')->updateOrInsert(['id' => $id], ['account_id' => null, 'name' => $name,'statements' => json_encode($statements, JSON_THROW_ON_ERROR),'created_at' => now(),'updated_at' => now()]);
            DB::table('site_management_roles')->updateOrInsert(['id' => $id], ['account_id' => null, 'name' => $name,'created_at' => now(),'updated_at' => now()]);
            DB::table('site_management_role_policy_attachments')->updateOrInsert(['role_id' => $id,'policy_id' => $id], []);
        }
        DB::table('account_principals')->orderBy('id')->each(static function (object $principal): void {
            app(ProvisionPrincipalInterface::class)->process(
                new ProvisionPrincipalInput(new IdentityIdentifier($principal->identity_id), new AccountIdentifier($principal->account_id)),
                new ProvisionPrincipalOutput(),
            );
        });
    }
}
