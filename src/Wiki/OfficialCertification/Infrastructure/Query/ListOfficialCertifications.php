<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Query;

use Application\Models\Wiki\OfficialCertification;
use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic;
use Application\Models\Wiki\WikiGroupBasic;
use Application\Models\Wiki\WikiSongBasic;
use Application\Models\Wiki\WikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInputPort;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsOutputPort;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationListItemReadModel;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationOwnerAccountReadModel;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

readonly class ListOfficialCertifications implements ListOfficialCertificationsInterface
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
    public function process(ListOfficialCertificationsInputPort $input, ListOfficialCertificationsOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        if (! $this->canReadOfficialCertifications($principal)) {
            throw new DisallowedException();
        }

        $query = OfficialCertification::query()
            ->with([
                'ownerAccount',
                'wikis' => fn ($query) => $query
                    ->select('wikis.*', 'wiki_images.image_path as image_path', 'wiki_images.alt_text as image_alt_text', 'wiki_images.is_hidden as image_is_hidden')
                    ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
                    ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
                    ->whereIn('wikis.resource_type', array_keys(self::BASIC_RELATIONS))
                    ->orderBy('wikis.language'),
            ])
            ->orderByDesc('official_certifications.requested_at')
            ->orderByDesc('official_certifications.updated_at');

        if ($input->status() !== null) {
            $query->where('official_certifications.status', $input->status()->value);
        }

        /** @var LengthAwarePaginator<int, OfficialCertification> $paginator */
        $paginator = $query->paginate($input->perPage());
        $certifications = $paginator->items();

        $output->output(
            array_map(
                fn (OfficialCertification $certification): OfficialCertificationListItemReadModel => $this->toReadModel(
                    $certification,
                ),
                $certifications,
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private function canReadOfficialCertifications(Principal $principal): bool
    {
        return array_all(ResourceType::cases(), fn ($resourceType) => $this->policyEvaluator->evaluate(
            $principal,
            Action::OFFICIAL_CERTIFICATION_READ,
            new Resource(type: $resourceType),
        ));

    }

    private function toReadModel(
        OfficialCertification $certification,
    ): OfficialCertificationListItemReadModel {
        $ownerAccount = $certification->ownerAccount;

        return new OfficialCertificationListItemReadModel(
            certificationIdentifier: $certification->id,
            resourceType: $certification->resource_type,
            translationSetIdentifier: $certification->translation_set_identifier,
            ownerAccount: $ownerAccount === null ? null : new OfficialCertificationOwnerAccountReadModel(
                accountIdentifier: $ownerAccount->id,
                email: $ownerAccount->email,
                type: $ownerAccount->type,
                name: $ownerAccount->name,
                status: $ownerAccount->status,
                category: $ownerAccount->category,
            ),
            wikis: $certification->wikis
                ->map(fn (WikiModel $wiki): WikiListItemReadModel => $this->toWikiReadModel($wiki))
                ->all(),
            status: $certification->status,
            requestedAt: $this->formatDateTime($certification->requested_at) ?? '',
            approvedAt: $this->formatDateTime($certification->approved_at),
            rejectedAt: $this->formatDateTime($certification->rejected_at),
        );
    }

    private function toWikiReadModel(WikiModel $wiki): WikiListItemReadModel
    {
        $basic = $this->basicModel($wiki);

        return new WikiListItemReadModel(
            wikiIdentifier: $wiki->id,
            translationSetIdentifier: $wiki->translation_set_identifier,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            version: $wiki->version,
            themeColor: $wiki->theme_color,
            fontStyle: $wiki->font_style,
            title: $wiki->title,
            metaDescription: $wiki->meta_description,
            keywords: $wiki->keywords,
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->getAttribute('image_path')),
            imageAltText: $wiki->getAttribute('image_alt_text'),
            isHidden: $this->nullableBool($wiki->getAttribute('image_is_hidden')),
            name: (string) $basic->getAttribute('name'),
            normalizedName: (string) $basic->getAttribute('normalized_name'),
            publishedAt: $this->formatDateTime($wiki->published_at),
            updatedAt: $this->formatDateTime($wiki->updated_at),
            isOfficial: $wiki->owner_account_id !== null,
        );
    }

    private function basicModel(WikiModel $wiki): Model
    {
        $relation = self::BASIC_RELATIONS[$wiki->resource_type] ?? null;
        if ($relation === null) {
            throw new InvalidArgumentException("Unsupported wiki resource type: {$wiki->resource_type}");
        }

        $basic = $wiki->{$relation};
        if (
            ! $basic instanceof WikiTalentBasic
            && ! $basic instanceof WikiGroupBasic
            && ! $basic instanceof WikiAgencyBasic
            && ! $basic instanceof WikiSongBasic
        ) {
            throw new InvalidArgumentException("Basic not found for Wiki: {$wiki->id}");
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

    private function nullableBool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}
