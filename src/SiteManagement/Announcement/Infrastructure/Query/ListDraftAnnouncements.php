<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Query;

use Application\Models\SiteManagement\DraftAnnouncement as DraftAnnouncementModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Infrastructure\Trait\WhereLike;
use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInputPort;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsOutputPort;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class ListDraftAnnouncements implements ListDraftAnnouncementsInterface
{
    use WhereLike;

    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(ListDraftAnnouncementsInputPort $input, ListDraftAnnouncementsOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_VIEW_DRAFT, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $direction = strtolower($input->order());
        if (! in_array($input->sort(), ['created_at'], true) || ! in_array($direction, ['asc', 'desc'], true) || $input->perPage() < 1 || $input->perPage() > 100) {
            throw new InvalidArgumentException('Invalid draft announcement list options.');
        }
        $query = DraftAnnouncementModel::query()->whereNull('deleted_at');
        if ($input->searchWords() !== '') {
            $query->where(function (Builder $query) use ($input): void {
                foreach (Language::cases() as $language) {
                    $query->orWhere(fn (Builder $query) => $this->whereLike($query, 'contents->' . $language->value . '->title', $input->searchWords()));
                    $query->orWhere(fn (Builder $query) => $this->whereLike($query, 'contents->' . $language->value . '->content', $input->searchWords()));
                }
            });
        }
        /** @var LengthAwarePaginator<int, DraftAnnouncementModel> $paginator */
        $paginator = $query->orderBy($input->sort(), $direction)->orderBy('id')->paginate($input->perPage());
        $announcements = array_map(static fn (DraftAnnouncementModel $model): DraftAnnouncementReadModel => new DraftAnnouncementReadModel(
            $model->id,
            $model->source_language,
            $model->category,
            $model->contents,
        ), array_values($paginator->items()));
        $output->output($announcements, $paginator->currentPage(), $paginator->lastPage(), $paginator->total(), $paginator->perPage());
    }
}
