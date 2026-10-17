<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('useDb')]
class MasterDataSeederTest extends TestCase
{
    public function testProductionSeedingCreatesOnlyMastersAndPreservesIdentifiersOnRepeat(): void
    {
        $this->app()->instance('env', 'production');
        $command = $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
        self::assertInstanceOf(PendingCommand::class, $command);
        $command->assertExitCode(0)->run();

        $before = [];
        foreach (['wiki_policies', 'wiki_roles', 'account_policies', 'account_roles', 'site_management_policies', 'site_management_roles'] as $table) {
            $before[$table] = DB::table($table)->whereNull('account_id')->orderBy('name')->pluck('id', 'name')->all();
            self::assertNotEmpty($before[$table], $table);
        }
        $attachments = DB::table('wiki_role_policy_attachments')->count();

        // Master-only execution is safe even when APP_ENV allows development samples.
        $this->app()->instance('env', 'local');
        $this->seed(MasterDataSeeder::class);

        foreach ($before as $table => $identifiers) {
            self::assertSame($identifiers, DB::table($table)->whereNull('account_id')->orderBy('name')->pluck('id', 'name')->all(), $table);
        }
        self::assertSame($attachments, DB::table('wiki_role_policy_attachments')->count());
        self::assertSame(0, DB::table('identities')->count());
        self::assertSame(0, DB::table('accounts')->count());
        self::assertSame(0, DB::table('wikis')->count());
    }

    public function testRepeatedMasterSeedingRestoresCanonicalPolicyAndRoleContents(): void
    {
        $this->seed(MasterDataSeeder::class);
        $policy = DB::table('wiki_policies')->whereNull('account_id')->where('name', 'GLOBAL_CREATE')->first();
        $role = DB::table('wiki_roles')->whereNull('account_id')->where('name', 'Collaborator')->first();
        self::assertNotNull($policy);
        self::assertNotNull($role);
        DB::table('wiki_policies')->where('id', $policy->id)->update(['statements' => '[]']);
        DB::table('wiki_role_policy_attachments')->where('role_id', $role->id)->delete();

        $this->seed(MasterDataSeeder::class);

        $this->assertDatabaseHas('wiki_policies', ['id' => $policy->id, 'name' => 'GLOBAL_CREATE']);
        self::assertNotSame('[]', DB::table('wiki_policies')->where('id', $policy->id)->value('statements'));
        $this->assertDatabaseHas('wiki_role_policy_attachments', ['role_id' => $role->id, 'policy_id' => $policy->id]);
    }
}
