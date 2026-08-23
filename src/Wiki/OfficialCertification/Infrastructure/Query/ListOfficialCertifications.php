<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Query;

use Application\Models\Account\Account;
use Application\Models\Wiki\OfficialCertification;
use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic;
use Application\Models\Wiki\WikiGroupBasic;
use Application\Models\Wiki\WikiSongBasic;
use Application\Models\Wiki\WikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInputPort;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsOutputPort;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationListItemReadModel;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationOwnerAccountReadModel;
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
            ->orderByDesc('official_certifications.requested_at')
            ->orderByDesc('official_certifications.updated_at');

        if ($input->status() !== null) {
            $query->where('official_certifications.status', $input->status()->value);
        }

        /** @var LengthAwarePaginator<int, OfficialCertification> $paginator */
        $paginator = $query->paginate($input->perPage());
        $certifications = $paginator->items();
        $accountMap = $this->accountMap($certifications);
        $wikiMap = $this->wikiMap($certifications);

        $output->output(
            array_map(
                fn (OfficialCertification $certification): OfficialCertificationListItemReadModel => $this->toReadModel(
                    $certification,
                    $accountMap[$certification->owner_account_id] ?? null,
                    $wikiMap[$certification->translation_set_identifier] ?? [],
                ),
                $certifications,
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private function canReadOfficialCertifications(\Source\Wiki\Principal\Domain\Entity\Principal $principal): bool
    {
        return array_all(ResourceType::cases(), fn ($resourceType) => $this->policyEvaluator->evaluate(
            $principal,
            Action::OFFICIAL_CERTIFICATION_READ,
            new Resource(type: $resourceType),
        ));

    }

    /**
     * @param list<OfficialCertification> $certifications
     * @return array<string, OfficialCertificationOwnerAccountReadModel>
     */
    private function accountMap(array $certifications): array
    {
        $accountIdentifiers = array_values(array_unique(array_map(
            static fn (OfficialCertification $certification): string => $certification->owner_account_id,
            $certifications,
        )));

        /** @var EloquentCollection<int, Account> $accounts */
        $accounts = Account::query()
            ->whereIn('id', $accountIdentifiers)
            ->get();

        $map = [];
        foreach ($accounts as $account) {
            $map[$account->id] = new OfficialCertificationOwnerAccountReadModel(
                accountIdentifier: $account->id,
                email: $account->email,
                type: $account->type,
                name: $account->name,
                status: $account->status,
                category: $account->category,
            );
        }

        return $map;
    }

    /**
     * @param list<OfficialCertification> $certifications
     * @return array<string, list<WikiListItemReadModel>>
     */
    private function wikiMap(array $certifications): array
    {
        $translationSetIdentifiers = array_values(array_unique(array_map(
            static fn (OfficialCertification $certification): string => $certification->translation_set_identifier,
            $certifications,
        )));

        /** @var EloquentCollection<int, WikiModel> $wikis */
        $wikis = WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as image_path', 'wiki_images.alt_text as image_alt_text', 'wiki_images.is_hidden as image_is_hidden')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->whereIn('wikis.translation_set_identifier', $translationSetIdentifiers)
            ->whereIn('wikis.resource_type', array_keys(self::BASIC_RELATIONS))
            ->orderBy('wikis.translation_set_identifier')
            ->orderBy('wikis.language')
            ->get();

        $map = [];
        foreach ($wikis as $wiki) {
            $map[$wiki->translation_set_identifier][] = $this->toWikiReadModel($wiki);
        }

        return $map;
    }

    /**
     * @param list<WikiListItemReadModel> $wikis
     */
    private function toReadModel(
        OfficialCertification $certification,
        ?OfficialCertificationOwnerAccountReadModel $ownerAccount,
        array $wikis,
    ): OfficialCertificationListItemReadModel {
        return new OfficialCertificationListItemReadModel(
            certificationIdentifier: $certification->id,
            resourceType: $certification->resource_type,
            translationSetIdentifier: $certification->translation_set_identifier,
            ownerAccount: $ownerAccount,
            wikis: $wikis,
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
