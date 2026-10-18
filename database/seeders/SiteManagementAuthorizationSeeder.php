<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Condition;
use Source\SiteManagement\Principal\Domain\ValueObject\Effect;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;

class SiteManagementAuthorizationSeeder extends Seeder
{
    public const string GENERAL_ROLE = '69200000-0000-7000-8000-000000000001';
    public const string OPERATOR_ROLE = '69200000-0000-7000-8000-000000000002';

    public const string ADMINISTRATOR_ROLE = '69200000-0000-7000-8000-000000000003';

    public function run(): void
    {
        $rolePolicies = [
            self::GENERAL_ROLE => ['General', 'general', [new Statement(Effect::ALLOW, [Action::CONTACT_VIEW], [ResourceType::CONTACT], Condition::OWN_CONTACT)]],
            self::OPERATOR_ROLE => ['Operator', 'administrator', [new Statement(Effect::ALLOW, [Action::ANNOUNCEMENT_VIEW_DRAFT, Action::ANNOUNCEMENT_CREATE, Action::ANNOUNCEMENT_UPDATE, Action::ANNOUNCEMENT_PUBLISH, Action::ANNOUNCEMENT_TRANSLATE, Action::ANNOUNCEMENT_DELETE, Action::CONTACT_VIEW, Action::CONTACT_REPLY], [ResourceType::ANNOUNCEMENT, ResourceType::CONTACT])]],
            self::ADMINISTRATOR_ROLE => ['Administrator', 'principal_group_management', [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP])]],
        ];
        foreach ($rolePolicies as $id => [$name, $policyName, $policyStatements]) {
            $statements = array_map(static fn (Statement $statement): array => [
                'effect' => $statement->effect()->value,
                'actions' => array_map(static fn (Action $action): string => $action->value, $statement->actions()),
                'resource_types' => array_map(static fn (ResourceType $type): string => $type->value, $statement->resourceTypes()),
                'condition' => $statement->condition()?->value,
            ], $policyStatements);
            DB::table('site_management_policies')->updateOrInsert(['id' => $id], ['account_id' => null, 'name' => $policyName,'statements' => json_encode($statements, JSON_THROW_ON_ERROR),'created_at' => now(),'updated_at' => now()]);
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
