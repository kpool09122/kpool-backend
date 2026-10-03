<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Infrastructure\Service\ImageService;
use Tests\TestCase;

#[Group('useDb')]
class ImageServiceDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::commit();
        config(['filesystems.image_disk' => 'public']);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::beginTransaction();
        parent::tearDown();
    }

    public function testDeletesImmediatelyWithoutTransaction(): void
    {
        $disk = Storage::disk('public');
        $disk->put('images/old.webp', 'old');

        $this->assertTrue((new ImageService(new NullLogger()))->delete(new ImagePath('images/old.webp')));
        $disk->assertMissing('images/old.webp');
    }

    public function testDeletesOnlyAfterOutermostCommit(): void
    {
        $disk = Storage::disk('public');
        $disk->put('images/old.webp', 'old');
        DB::beginTransaction();
        DB::beginTransaction();

        $this->assertTrue((new ImageService(new NullLogger()))->delete(new ImagePath('images/old.webp')));
        $disk->assertExists('images/old.webp');
        DB::commit();
        $disk->assertExists('images/old.webp');
        DB::commit();
        $disk->assertMissing('images/old.webp');
    }

    public function testRollbackKeepsImageAndDiscardsScheduledDeletion(): void
    {
        $disk = Storage::disk('public');
        $disk->put('images/old.webp', 'old');
        DB::beginTransaction();
        (new ImageService(new NullLogger()))->delete(new ImagePath('images/old.webp'));
        DB::rollBack();
        DB::beginTransaction();
        DB::commit();

        $disk->assertExists('images/old.webp');
    }

    /** @return array<string, array{bool}> */
    public static function deletionFailures(): array
    {
        return ['exception' => [true], 'false result' => [false]];
    }

    #[DataProvider('deletionFailures')]
    public function testDeletionFailureIsLoggedWithoutFailingCommit(bool $throws): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $expectation = $disk->shouldReceive('delete')->once()->with('images/old.webp');
        $context = ['imagePath' => 'images/old.webp'];
        if ($throws) {
            $exception = new RuntimeException('Storage unavailable');
            $expectation->andThrow($exception);
            $context['exception'] = $exception;
        } else {
            $expectation->andReturn(false);
        }
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once()->with('Failed to delete image.', $context);
        DB::beginTransaction();
        (new ImageService($logger))->delete(new ImagePath('images/old.webp'));
        DB::commit();

        $this->assertSame(0, DB::transactionLevel());
    }
}
