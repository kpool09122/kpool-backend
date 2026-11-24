<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, AccountSnapshotDocument> $documents
 * @property string $account_email
 * @property string $account_type
 * @property string $account_name
 * @property string $account_status
 * @property string $account_phone
 * @property string $account_address_country_code
 * @property ?string $account_address_administrative_area_code
 * @property string $account_address_postal_code
 * @property ?string $account_address_locality
 * @property string $account_address_line1
 * @property ?string $account_address_line2
 * @property string $id
 * @property string $account_id
 * @property string $status
 * @property CarbonImmutable $saved_at
 */
#[Fillable(['id', 'account_id', 'status', 'saved_at', 'account_email', 'account_type', 'account_name', 'account_status', 'account_phone', 'account_address_country_code', 'account_address_administrative_area_code', 'account_address_postal_code', 'account_address_locality', 'account_address_line1', 'account_address_line2'])]
#[Table(name: 'account_snapshots', keyType: 'string')]
class AccountSnapshot extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $incrementing = false;
    public $timestamps = false;

    /** @return HasMany<AccountSnapshotDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(AccountSnapshotDocument::class, 'snapshot_id')->orderBy('document_type');
    }

    protected function casts(): array
    {
        return ['saved_at' => 'immutable_datetime'];
    }
}
