<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read AccountSnapshot|null $snapshot
 * @property string $snapshot_id
 * @property string $document_type
 * @property string $document_path
 * @property string $content_hash
 * @property CarbonImmutable $uploaded_at
 */
#[Fillable(['snapshot_id','document_type','document_path','content_hash','uploaded_at'])]
#[Table(name: 'account_snapshot_documents')]
class AccountSnapshotDocument extends Model
{
    public $timestamps = false;

    /** @return BelongsTo<AccountSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AccountSnapshot::class, 'snapshot_id');
    }

    protected function casts(): array
    {
        return ['uploaded_at' => 'immutable_datetime'];
    }
}
