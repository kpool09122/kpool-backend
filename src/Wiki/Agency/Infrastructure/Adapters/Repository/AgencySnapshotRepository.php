<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\AgencySnapshot as AgencySnapshotModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class AgencySnapshotRepository implements AgencySnapshotRepositoryInterface
{
    public function save(AgencySnapshot $snapshot): void
    {
        AgencySnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'agency_id' => (string)$snapshot->agencyIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'language' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'normalized_name' => $snapshot->normalizedName(),
            'CEO' => (string)$snapshot->CEO(),
            'normalized_CEO' => $snapshot->normalizedCEO(),
            'founded_in' => $snapshot->foundedIn()?->value(),
            'description' => (string)$snapshot->description(),
            'version' => $snapshot->version()->value(),
            'created_at' => $snapshot->createdAt(),
        ]);
    }

    public function findByAgencyIdentifier(AgencyIdentifier $agencyIdentifier): array
    {
        $models = AgencySnapshotModel::query()
            ->where('agency_id', (string)$agencyIdentifier)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn (AgencySnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    public function findByAgencyAndVersion(
        AgencyIdentifier $agencyIdentifier,
        Version $version
    ): ?AgencySnapshot {
        $model = AgencySnapshotModel::query()
            ->where('agency_id', (string)$agencyIdentifier)
            ->where('version', $version->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    private function toEntity(AgencySnapshotModel $model): AgencySnapshot
    {
        return new AgencySnapshot(
            new AgencySnapshotIdentifier($model->id),
            new AgencyIdentifier($model->agency_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->language),
            new AgencyName($model->name),
            $model->normalized_name,
            new CEO($model->CEO),
            $model->normalized_CEO,
            $model->founded_in ? new FoundedIn($model->founded_in->toDateTimeImmutable()) : null,
            new Description($model->description),
            new Version($model->version),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
