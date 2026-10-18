<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Query;

use Application\Models\SiteManagement\Announcement as AnnouncementModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Trait\WhereLike;
use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInputPort;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsOutputPort;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use UnexpectedValueException;

readonly class ListAnnouncements implements ListAnnouncementsInterface
{
    use WhereLike;

    public function process(ListAnnouncementsInputPort $input, ListAnnouncementsOutputPort $output): void
    {
        $language = $input->language()->value;
        $direction = strtolower($input->order());
        $sorts = ['published_date' => 'published_date', 'title' => 'contents->' . $language . '->title'];
        if (! isset($sorts[$input->sort()]) || ! in_array($direction, ['asc', 'desc'], true) || $input->perPage() < 1 || $input->perPage() > 100) {
            throw new InvalidArgumentException('Invalid announcement list options.');
        }
        $query = AnnouncementModel::query()->whereNotNull('contents->' . $language);
        if ($input->searchWords() !== '') {
            $query->where(function (Builder $query) use ($input, $language): void {
                $this->whereLike($query, 'contents->' . $language . '->title', $input->searchWords());
                $query->orWhere(fn (Builder $query) => $this->whereLike($query, 'contents->' . $language . '->content', $input->searchWords()));
            });
        }
        /** @var LengthAwarePaginator<int, AnnouncementModel> $paginator */
        $paginator = $query->orderBy($sorts[$input->sort()], $direction)->orderBy('id')
            ->paginate($input->perPage());
        $result = [];
        foreach ($paginator->items() as $model) {
            $content = $model->contents[$language] ?? null;
            if ($content !== null) {
                $result[] = new AnnouncementReadModel(
                    $model->id,
                    Category::from($model->category)->name,
                    $content['title'],
                    $content['content'],
                    ($model->published_date ?? throw new UnexpectedValueException('Publication date is missing.'))->toDateTimeImmutable(),
                );
            }
        }

        $output->output($result, $paginator->currentPage(), $paginator->lastPage(), $paginator->total(), $paginator->perPage());
    }
}
