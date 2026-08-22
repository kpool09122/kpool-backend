<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisOutput;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListMyOwnedWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsOnlyCurrentAccountOwnedWikis(): void
    {
        $currentAccountId = '01965bb2-bcc9-7c6f-8b90-89f7f217f101';
        $otherAccountId = '01965bb2-bcc9-7c6f-8b90-89f7f217f102';

        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f201', 'agency', 'Current Agency', $currentAccountId, '2026-08-04 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f202', 'group', 'Current Group', $currentAccountId, '2026-08-03 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f203', 'talent', 'Other Talent', $otherAccountId, '2026-08-02 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f204', 'song', 'No Owner', null, '2026-08-01 00:00:00');

        $payload = $this->process($currentAccountId, AccountCategory::AGENCY)->toArray();

        $this->assertSame('agency', $payload['accountCategory']);
        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f217f201'], array_column($payload['primaryOwnedWikis'], 'wikiIdentifier'));
        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f217f202'], array_column($payload['otherOwnedWikis'], 'wikiIdentifier'));
        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(1, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
        $this->assertSame('agency', $payload['primaryOwnedWikis'][0]['resourceType']);
        $this->assertSame('Current Agency', $payload['primaryOwnedWikis'][0]['name']);
        $this->assertSame('ag-current-agency', $payload['primaryOwnedWikis'][0]['slug']);
        $this->assertSame('ja', $payload['primaryOwnedWikis'][0]['language']);
    }

    #[Group('useDb')]
    public function testProcessClassifiesTalentAccountOwnedWikis(): void
    {
        $accountId = '01965bb2-bcc9-7c6f-8b90-89f7f217f301';

        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f302', 'talent', 'Owned Talent', $accountId, '2026-08-03 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f303', 'agency', 'Owned Agency', $accountId, '2026-08-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f304', 'song', 'Owned Song', $accountId, '2026-08-02 00:00:00');

        $payload = $this->process($accountId, AccountCategory::TALENT)->toArray();

        $this->assertSame(['talent'], array_column($payload['primaryOwnedWikis'], 'resourceType'));
        $this->assertSame(['song', 'agency'], array_column($payload['otherOwnedWikis'], 'resourceType'));
    }

    #[Group('useDb')]
    public function testProcessClassifiesGeneralAccountOwnedWikisAsOther(): void
    {
        $accountId = '01965bb2-bcc9-7c6f-8b90-89f7f217f401';

        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f402', 'talent', 'Owned Talent', $accountId, '2026-08-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f403', 'group', 'Owned Group', $accountId, '2026-08-02 00:00:00');

        $payload = $this->process($accountId, AccountCategory::GENERAL)->toArray();

        $this->assertSame([], $payload['primaryOwnedWikis']);
        $this->assertSame(['group', 'talent'], array_column($payload['otherOwnedWikis'], 'resourceType'));
    }

    #[Group('useDb')]
    public function testProcessPaginatesOnlyOtherOwnedWikis(): void
    {
        $accountId = '01965bb2-bcc9-7c6f-8b90-89f7f217f501';

        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f502', 'agency', 'Owned Agency', $accountId, '2026-08-04 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f503', 'group', 'Owned Group', $accountId, '2026-08-03 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f504', 'song', 'Owned Song', $accountId, '2026-08-02 00:00:00');

        $payload = $this->process($accountId, AccountCategory::AGENCY, 1)->toArray();

        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f217f502'], array_column($payload['primaryOwnedWikis'], 'wikiIdentifier'));
        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f217f503'], array_column($payload['otherOwnedWikis'], 'wikiIdentifier'));
        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(2, $payload['total']);
        $this->assertSame(1, $payload['per_page']);
    }

    private function listMyOwnedWikis(): ListMyOwnedWikisInterface
    {
        return $this->app->make(ListMyOwnedWikisInterface::class);
    }

    private function process(string $accountId, AccountCategory $accountCategory, ?int $perPage = null): ListMyOwnedWikisOutput
    {
        $output = new ListMyOwnedWikisOutput();
        $this->listMyOwnedWikis()->process(
            new ListMyOwnedWikisInput(new AccountIdentifier($accountId), $accountCategory, $perPage),
            $output,
        );

        return $output;
    }

    private function createWiki(string $wikiId, string $resourceType, string $name, ?string $ownerAccountId, string $updatedAt): void
    {
        CreateWiki::create(
            $wikiId,
            $resourceType,
            [
                'slug' => $this->slug($resourceType, $name),
                'language' => 'ja',
                'owner_account_id' => $ownerAccountId,
                'published_at' => '2026-08-01 00:00:00',
                'updated_at' => '2026-08-01 00:00:00',
            ],
            [
                'name' => $name,
                'normalized_name' => strtolower(str_replace(' ', '-', $name)),
            ],
        );

        DB::table('wikis')->where('id', $wikiId)->update(['updated_at' => $updatedAt]);
    }

    private function slug(string $resourceType, string $name): string
    {
        $prefix = match ($resourceType) {
            'agency' => 'ag',
            'group' => 'gr',
            'song' => 'sg',
            'talent' => 'tl',
            default => throw new \InvalidArgumentException("Unknown resource type: {$resourceType}"),
        };

        return $prefix . '-' . strtolower(str_replace(' ', '-', $name));
    }
}
