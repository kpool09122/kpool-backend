<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Factory\WikiHistoryFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiHistoryFactory = $this->app->make(WikiHistoryFactoryInterface::class);
        $this->assertInstanceOf(WikiHistoryFactory::class, $wikiHistoryFactory);
    }

    /**
     * 正常系: WikiHistory Entityが正しく作成されること（wikiIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithWikiIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $actorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new Name('TWICE');

        $wikiHistoryFactory = $this->app->make(WikiHistoryFactoryInterface::class);
        $wikiHistory = $wikiHistoryFactory->create(
            $actionType,
            $actorIdentifier,
            $submitterIdentifier,
            $wikiIdentifier,
            null,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$wikiHistory->historyIdentifier()));
        $this->assertSame($actionType, $wikiHistory->actionType());
        $this->assertSame((string)$actorIdentifier, (string)$wikiHistory->actorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$wikiHistory->submitterIdentifier());
        $this->assertSame((string)$wikiIdentifier, (string)$wikiHistory->wikiIdentifier());
        $this->assertNull($wikiHistory->draftWikiIdentifier());
        $this->assertSame($fromStatus, $wikiHistory->fromStatus());
        $this->assertSame($toStatus, $wikiHistory->toStatus());
        $this->assertNull($wikiHistory->fromVersion());
        $this->assertNull($wikiHistory->toVersion());
        $this->assertSame($subjectName, $wikiHistory->subjectName());
        $this->assertNotNull($wikiHistory->recordedAt());
    }

    /**
     * 正常系: WikiHistory Entityが正しく作成されること（draftWikiIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftWikiIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $actorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new Name('NewJeans');

        $wikiHistoryFactory = $this->app->make(WikiHistoryFactoryInterface::class);
        $wikiHistory = $wikiHistoryFactory->create(
            $actionType,
            $actorIdentifier,
            null,
            null,
            $draftWikiIdentifier,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$wikiHistory->historyIdentifier()));
        $this->assertSame($actionType, $wikiHistory->actionType());
        $this->assertSame((string)$actorIdentifier, (string)$wikiHistory->actorIdentifier());
        $this->assertNull($wikiHistory->submitterIdentifier());
        $this->assertNull($wikiHistory->wikiIdentifier());
        $this->assertSame((string)$draftWikiIdentifier, (string)$wikiHistory->draftWikiIdentifier());
        $this->assertNull($wikiHistory->fromStatus());
        $this->assertSame($toStatus, $wikiHistory->toStatus());
        $this->assertNull($wikiHistory->fromVersion());
        $this->assertNull($wikiHistory->toVersion());
        $this->assertSame($subjectName, $wikiHistory->subjectName());
        $this->assertNotNull($wikiHistory->recordedAt());
    }

    /**
     * 正常系: WikiHistory Entityが正しく作成されること（Rollback）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithRollback(): void
    {
        $actionType = HistoryActionType::Rollback;
        $actorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $fromVersion = new Version(5);
        $toVersion = new Version(2);
        $subjectName = new Name('TWICE');

        $wikiHistoryFactory = $this->app->make(WikiHistoryFactoryInterface::class);
        $wikiHistory = $wikiHistoryFactory->create(
            $actionType,
            $actorIdentifier,
            null,
            $wikiIdentifier,
            null,
            null,
            null,
            $fromVersion,
            $toVersion,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$wikiHistory->historyIdentifier()));
        $this->assertSame($actionType, $wikiHistory->actionType());
        $this->assertSame((string)$actorIdentifier, (string)$wikiHistory->actorIdentifier());
        $this->assertNull($wikiHistory->submitterIdentifier());
        $this->assertSame((string)$wikiIdentifier, (string)$wikiHistory->wikiIdentifier());
        $this->assertNull($wikiHistory->draftWikiIdentifier());
        $this->assertNull($wikiHistory->fromStatus());
        $this->assertNull($wikiHistory->toStatus());
        $this->assertSame($fromVersion->value(), $wikiHistory->fromVersion()->value());
        $this->assertSame($toVersion->value(), $wikiHistory->toVersion()->value());
        $this->assertSame($subjectName, $wikiHistory->subjectName());
        $this->assertNotNull($wikiHistory->recordedAt());
    }
}
