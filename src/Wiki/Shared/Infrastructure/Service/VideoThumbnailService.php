<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Infrastructure\Service;

use Application\Models\Wiki\VideoThumbnail;
use Illuminate\Support\Facades\DB;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Shared\Application\Service\VideoThumbnailServiceInterface;

final readonly class VideoThumbnailService implements VideoThumbnailServiceInterface
{
    public function __construct(private ImageServiceInterface $imageService)
    {
    }

    public function track(ImagePath $path): void
    {
        VideoThumbnail::query()->insertOrIgnore(['path' => (string) $path]);
    }

    public function releaseUnused(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => $this->collectUnused());

            return;
        }
        $this->collectUnused();
    }

    private function collectUnused(): void
    {
        $query = VideoThumbnail::query()->whereDoesntHave('videoLinks')->whereDoesntHave('playlistItems');
        foreach ($query->lazyById(100, 'path') as $row) {
            $path = new ImagePath($row->path);
            if ($this->imageService->delete($path)) {
                VideoThumbnail::query()->whereKey((string) $path)->delete();
            }
        }
    }
}
