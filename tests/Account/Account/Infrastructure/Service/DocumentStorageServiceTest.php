<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Account\Infrastructure\Service\DocumentStorageService;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class DocumentStorageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::commit();
        Storage::fake('verification-documents');
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::beginTransaction();
        parent::tearDown();
    }

    public function testFailedWriteDoesNotReturnDocumentPath(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('verification-documents')->andReturn($disk);
        $this->expectException(UnableToWriteFile::class);

        (new DocumentStorageService(new NullLogger()))->storeForAccount(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            DocumentType::BUSINESS_REGISTRATION,
            AccountDocumentFileType::PDF,
            'document',
        );
    }

    public function testDeletesImmediatelyWithoutTransaction(): void
    {
        $disk = Storage::disk('verification-documents');
        $disk->put('old.pdf', 'old');

        (new DocumentStorageService(new NullLogger()))->delete(new DocumentPath('old.pdf'));

        $this->assertFalse($disk->exists('old.pdf'));
    }

    public function testDeletesOnlyAfterOutermostCommit(): void
    {
        $disk = Storage::disk('verification-documents');
        $disk->put('old.pdf', 'old');
        DB::beginTransaction();
        DB::beginTransaction();

        (new DocumentStorageService(new NullLogger()))->delete(new DocumentPath('old.pdf'));
        DB::commit();
        $this->assertTrue($disk->exists('old.pdf'));
        DB::commit();

        $this->assertFalse($disk->exists('old.pdf'));
    }

    public function testRollbackKeepsOldDocumentAndRemovesNewDocument(): void
    {
        $disk = Storage::disk('verification-documents');
        $disk->put('old.pdf', 'old');
        $service = new DocumentStorageService(new NullLogger());
        DB::beginTransaction();
        $newPath = $service->storeForAccount(new AccountIdentifier(StrTestHelper::generateUuid()), DocumentType::BUSINESS_REGISTRATION, AccountDocumentFileType::PDF, 'new');
        $service->delete(new DocumentPath('old.pdf'));
        $this->assertTrue($disk->exists((string) $newPath));

        DB::rollBack();

        $this->assertTrue($disk->exists('old.pdf'));
        $this->assertFalse($disk->exists((string) $newPath));
    }

    public function testCommittedNewDocumentIsRetained(): void
    {
        DB::beginTransaction();
        $service = new DocumentStorageService(new NullLogger());
        $path = $service->storeForAccount(new AccountIdentifier(StrTestHelper::generateUuid()), DocumentType::BUSINESS_REGISTRATION, AccountDocumentFileType::PDF, 'new');
        DB::commit();
        DB::beginTransaction();
        DB::rollBack();

        $this->assertTrue(Storage::disk('verification-documents')->exists((string) $path));
    }

    public function testDeletionFailureIsLoggedWithoutFailingCommit(): void
    {
        $exception = new RuntimeException('Storage unavailable');
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('delete')->once()->with('old.pdf')->andThrow($exception);
        Storage::shouldReceive('disk')->with('verification-documents')->andReturn($disk);
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once()->with('Failed to delete document.', ['documentPath' => 'old.pdf', 'exception' => $exception]);
        DB::beginTransaction();
        (new DocumentStorageService($logger))->delete(new DocumentPath('old.pdf'));

        DB::commit();

        $this->assertSame(0, DB::transactionLevel());
    }
}
