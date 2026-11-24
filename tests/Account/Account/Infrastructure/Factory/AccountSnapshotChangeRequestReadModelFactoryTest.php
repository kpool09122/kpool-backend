<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use Application\Models\Account\AccountSnapshotChangeRequest;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Infrastructure\Factory\AccountSnapshotChangeRequestReadModelFactory;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\TestCase;

#[Group('useDb')]
class AccountSnapshotChangeRequestReadModelFactoryTest extends TestCase
{
    public function testProjectsPinnedOriginalAndPrivateProposal(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $model = AccountSnapshotChangeRequest::query()->with(['snapshot.documents', 'account.documents', 'reviewedSnapshot.documents'])->withCurrentSnapshotIdentifier()->findOrFail((string) $requestId);
        $data = $this->app()->make(AccountSnapshotChangeRequestReadModelFactory::class)->create($model)->toArray();
        self::assertSame('Test Account', $data['original']['account']['name']);
        self::assertSame('Proposed name', $data['proposal']['account']['name']);
        self::assertTrue($data['original']['isCurrent']);
        self::assertCount(2, $data['proposal']['documents']);
        self::assertSame((string) $id, $data['request']['accountIdentifier']);
    }
}
