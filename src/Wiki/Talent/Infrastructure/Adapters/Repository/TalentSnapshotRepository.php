<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\TalentSnapshot as TalentSnapshotModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;

class TalentSnapshotRepository implements TalentSnapshotRepositoryInterface
{
    public function save(TalentSnapshot $snapshot): void
    {
        /** @var TalentSnapshotModel $snapshotModel */
        $snapshotModel = TalentSnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'talent_id' => (string)$snapshot->talentIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'language' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'real_name' => (string)$snapshot->realName(),
            'agency_id' => $snapshot->agencyIdentifier() ? (string)$snapshot->agencyIdentifier() : null,
            'birthday' => $snapshot->birthday()?->value(),
            'career' => (string)$snapshot->career(),
            'image_link' => $snapshot->imageLink() ? (string)$snapshot->imageLink() : null,
            'relevant_video_links' => $snapshot->relevantVideoLinks()->toStringArray(),
            'version' => $snapshot->version()->value(),
            'created_at' => $snapshot->createdAt(),
        ]);

        $groupIds = $this->fromGroupIdentifiers($snapshot->groupIdentifiers());
        $snapshotModel->groups()->sync($groupIds);
    }

    public function findByTalentIdentifier(TalentIdentifier $talentIdentifier): array
    {
        $models = TalentSnapshotModel::query()
            ->with('groups')
            ->where('talent_id', (string)$talentIdentifier)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn (TalentSnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    public function findByTalentAndVersion(
        TalentIdentifier $talentIdentifier,
        Version $version
    ): ?TalentSnapshot {
        $model = TalentSnapshotModel::query()
            ->with('groups')
            ->where('talent_id', (string)$talentIdentifier)
            ->where('version', $version->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @param GroupIdentifier[] $groupIdentifiers
     * @return string[]
     */
    private function fromGroupIdentifiers(array $groupIdentifiers): array
    {
        return array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $groupIdentifiers,
        );
    }

    private function toEntity(TalentSnapshotModel $model): TalentSnapshot
    {
        $groupIdentifiers = $model->groups
            ->map(fn ($group) => new GroupIdentifier($group->id))
            ->toArray();

        return new TalentSnapshot(
            new TalentSnapshotIdentifier($model->id),
            new TalentIdentifier($model->talent_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->language),
            new TalentName($model->name),
            new RealName($model->real_name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            $groupIdentifiers,
            $model->birthday ? new Birthday($model->birthday->toDateTimeImmutable()) : null,
            new Career($model->career),
            $model->image_link ? new ImagePath($model->image_link) : null,
            RelevantVideoLinks::formStringArray($model->relevant_video_links ?? []),
            new Version($model->version),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
