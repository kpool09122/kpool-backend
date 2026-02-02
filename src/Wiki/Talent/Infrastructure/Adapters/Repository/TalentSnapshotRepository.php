<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group;
use Application\Models\Wiki\TalentSnapshot as TalentSnapshotModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;

class TalentSnapshotRepository implements TalentSnapshotRepositoryInterface
{
    public function save(TalentSnapshot $snapshot): void
    {
        /** @var TalentSnapshotModel $snapshotModel */
        $snapshotModel = TalentSnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'talent_id' => (string)$snapshot->talentIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'slug' => (string)$snapshot->slug(),
            'language' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'real_name' => (string)$snapshot->realName(),
            'agency_id' => $snapshot->agencyIdentifier() ? (string)$snapshot->agencyIdentifier() : null,
            'birthday' => $snapshot->birthday()?->value(),
            'career' => (string)$snapshot->career(),
            'version' => $snapshot->version()->value(),
            'created_at' => $snapshot->createdAt(),
            'editor_id' => $snapshot->editorIdentifier() ? (string)$snapshot->editorIdentifier() : null,
            'approver_id' => $snapshot->approverIdentifier() ? (string)$snapshot->approverIdentifier() : null,
            'merger_id' => $snapshot->mergerIdentifier() ? (string)$snapshot->mergerIdentifier() : null,
            'merged_at' => $snapshot->mergedAt(),
            'source_editor_id' => $snapshot->sourceEditorIdentifier() ? (string)$snapshot->sourceEditorIdentifier() : null,
            'translated_at' => $snapshot->translatedAt(),
            'approved_at' => $snapshot->approvedAt(),
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
     * @inheritDoc
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version
    ): array {
        $models = TalentSnapshotModel::query()
            ->with('groups')
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->where('version', $version->value())
            ->get();

        return $models->map(fn (TalentSnapshotModel $model) => $this->toEntity($model))->toArray();
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
            ->map(fn (Group $group) => new GroupIdentifier($group->id))
            ->toArray();

        return new TalentSnapshot(
            new TalentSnapshotIdentifier($model->id),
            new TalentIdentifier($model->talent_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->language),
            new TalentName($model->name),
            new RealName($model->real_name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            $groupIdentifiers,
            $model->birthday ? new Birthday($model->birthday->toDateTimeImmutable()) : null,
            new Career($model->career),
            new Version($model->version),
            $model->created_at->toDateTimeImmutable(),
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
            $model->merged_at?->toDateTimeImmutable(),
            $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
            $model->translated_at?->toDateTimeImmutable(),
            $model->approved_at?->toDateTimeImmutable(),
        );
    }
}
