<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactory;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $this->assertInstanceOf(TalentHistoryFactory::class, $talentHistoryFactory);
    }

    /**
     * 正常系: TalentHistory Entityが正しく作成されること（talentIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithTalentIdentifier(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;

        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $talentHistory = $talentHistoryFactory->create(
            $editorIdentifier,
            $submitterIdentifier,
            $talentIdentifier,
            null,
            $fromStatus,
            $toStatus,
        );

        $this->assertTrue(UlidValidator::isValid((string)$talentHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$talentHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$talentHistory->submitterIdentifier());
        $this->assertSame((string)$talentIdentifier, (string)$talentHistory->talentIdentifier());
        $this->assertNull($talentHistory->draftTalentIdentifier());
        $this->assertSame($fromStatus, $talentHistory->fromStatus());
        $this->assertSame($toStatus, $talentHistory->toStatus());
        $this->assertNotNull($talentHistory->recordedAt());
    }

    /**
     * 正常系: TalentHistory Entityが正しく作成されること（draftTalentIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftTalentIdentifier(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $draftTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;

        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $talentHistory = $talentHistoryFactory->create(
            $editorIdentifier,
            null,
            null,
            $draftTalentIdentifier,
            $fromStatus,
            $toStatus,
        );

        $this->assertTrue(UlidValidator::isValid((string)$talentHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$talentHistory->editorIdentifier());
        $this->assertNull($talentHistory->submitterIdentifier());
        $this->assertNull($talentHistory->talentIdentifier());
        $this->assertSame((string)$draftTalentIdentifier, (string)$talentHistory->draftTalentIdentifier());
        $this->assertNull($talentHistory->fromStatus());
        $this->assertSame($toStatus, $talentHistory->toStatus());
        $this->assertNotNull($talentHistory->recordedAt());
    }
}
