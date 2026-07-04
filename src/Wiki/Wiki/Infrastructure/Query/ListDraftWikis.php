<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki;
use Application\Models\Wiki\DraftWikiAgencyBasic;
use Application\Models\Wiki\DraftWikiGroupBasic;
use Application\Models\Wiki\DraftWikiSongBasic;
use Application\Models\Wiki\DraftWikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutputPort;

readonly class ListDraftWikis implements ListDraftWikisInterface
{
    /** @var array<string, string> */
    private const BASIC_RELATIONS = [
        ResourceType::TALENT->value => 'talentBasic',
        ResourceType::GROUP->value => 'groupBasic',
        ResourceType::AGENCY->value => 'agencyBasic',
        ResourceType::SONG->value => 'songBasic',
    ];

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListDraftWikisInputPort $input, ListDraftWikisOutputPort $output): void
    {
        $query = DraftWiki::query()
            ->with([
                'image',
                'talentBasic.groups',
                'groupBasic',
                'agencyBasic',
                'songBasic.groups',
                'songBasic.talents',
            ])
            ->whereIn(
                'draft_wikis.status',
                array_map(static fn (ApprovalStatus $status): string => $status->value, $input->statuses()),
            )
            ->orderBy('draft_wikis.edited_at', 'desc')
            ->orderBy('draft_wikis.updated_at', 'desc');

        if ($input->translationSetIdentifier() !== null) {
            $query->where('draft_wikis.translation_set_identifier', (string) $input->translationSetIdentifier());
        }

        if ($input->resourceType() !== null) {
            $query->where('draft_wikis.resource_type', $input->resourceType()->value);
        } else {
            $query->whereIn('draft_wikis.resource_type', array_keys(self::BASIC_RELATIONS));
        }

        /** @var LengthAwarePaginator<int, DraftWiki> $paginator */
        $paginator = $query->paginate($input->perPage());

        $this->authorize($input, $paginator->items());

        $output->output(
            array_map(
                fn (DraftWiki $wiki): DraftWikiListItemReadModel => $this->toReadModel($wiki),
                $paginator->items(),
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    /**
     * @param DraftWiki[] $wikis
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    private function authorize(ListDraftWikisInputPort $input, array $wikis): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        foreach ($wikis as $wiki) {
            if (! $this->policyEvaluator->evaluate($principal, Action::READ, $this->toResource($wiki))) {
                throw new DisallowedException();
            }
        }
    }

    private function toResource(DraftWiki $wiki): Resource
    {
        $resourceType = ResourceType::from($wiki->resource_type);

        if ($resourceType === ResourceType::AGENCY) {
            return new Resource(
                type: $resourceType,
                agencyId: $wiki->id,
                editorId: $wiki->editor_id,
            );
        }

        if ($resourceType === ResourceType::GROUP) {
            $basic = $wiki->groupBasic;
            if (! $basic instanceof DraftWikiGroupBasic) {
                throw new InvalidArgumentException("Group basic not found for DraftWiki: {$wiki->id}");
            }

            return new Resource(
                type: $resourceType,
                agencyId: $basic->agency_identifier,
                groupIds: [$wiki->id],
                editorId: $wiki->editor_id,
            );
        }

        if ($resourceType === ResourceType::TALENT) {
            $basic = $wiki->talentBasic;
            if (! $basic instanceof DraftWikiTalentBasic) {
                throw new InvalidArgumentException("Talent basic not found for DraftWiki: {$wiki->id}");
            }

            return new Resource(
                type: $resourceType,
                agencyId: $basic->agency_identifier,
                groupIds: $basic->groups->map(static fn (Model $group): string => (string) $group->id)->all(),
                talentIds: [$wiki->id],
                editorId: $wiki->editor_id,
            );
        }

        if ($resourceType === ResourceType::SONG) {
            $basic = $wiki->songBasic;
            if (! $basic instanceof DraftWikiSongBasic) {
                throw new InvalidArgumentException("Song basic not found for DraftWiki: {$wiki->id}");
            }

            return new Resource(
                type: $resourceType,
                agencyId: $basic->agency_identifier,
                groupIds: $basic->groups->map(static fn (Model $group): string => (string) $group->id)->all(),
                talentIds: $basic->talents->map(static fn (Model $talent): string => (string) $talent->id)->all(),
                editorId: $wiki->editor_id,
            );
        }

        throw new InvalidArgumentException("Unsupported draft wiki resource type: {$wiki->resource_type}");
    }

    private function toReadModel(DraftWiki $wiki): DraftWikiListItemReadModel
    {
        $basic = $this->basicModel($wiki);

        return new DraftWikiListItemReadModel(
            wikiIdentifier: $wiki->id,
            publishedWikiIdentifier: $wiki->published_wiki_id,
            translationSetIdentifier: $wiki->translation_set_identifier,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            themeColor: $wiki->theme_color,
            title: $wiki->title,
            metaDescription: $wiki->meta_description,
            keywords: $wiki->keywords,
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->image?->image_path),
            imageAltText: $wiki->image?->alt_text,
            status: $wiki->status,
            rejectionReason: $wiki->rejection_reason,
            name: $basic->name,
            normalizedName: $basic->normalized_name,
            editedAt: $this->formatDateTime($wiki->edited_at),
            approvedAt: $this->formatDateTime($wiki->approved_at),
            translatedAt: $this->formatDateTime($wiki->translated_at),
            mergedAt: $this->formatDateTime($wiki->merged_at),
        );
    }

    private function basicModel(DraftWiki $wiki): DraftWikiTalentBasic|DraftWikiGroupBasic|DraftWikiAgencyBasic|DraftWikiSongBasic
    {
        $relation = self::BASIC_RELATIONS[$wiki->resource_type] ?? null;
        if ($relation === null) {
            throw new InvalidArgumentException("Unsupported draft wiki resource type: {$wiki->resource_type}");
        }

        $basic = $wiki->{$relation};
        if (
            ! $basic instanceof DraftWikiTalentBasic
            && ! $basic instanceof DraftWikiGroupBasic
            && ! $basic instanceof DraftWikiAgencyBasic
            && ! $basic instanceof DraftWikiSongBasic
        ) {
            throw new InvalidArgumentException("Basic not found for DraftWiki: {$wiki->id}");
        }

        return $basic;
    }

    private function formatDateTime(mixed $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $dateTime->format(DateTimeInterface::ATOM);
        }

        return (string) $dateTime;
    }
}
