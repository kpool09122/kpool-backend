<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Collection<int, AccountCategoryChangeRequestDocument> $documents
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
 * @property string $current_account_category
 * @property string $requested_account_category
 * @property string $status
 * @property Carbon $requested_at
 * @property ?string $reviewed_by
 * @property ?Carbon $reviewed_at
 * @property array{code: string, detail: string|null}|null $rejection_reason
 */
#[Fillable(['id', 'account_id', 'current_account_category', 'requested_account_category', 'status', 'requested_at', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'account_email', 'account_type', 'account_name', 'account_status', 'account_phone', 'account_address_country_code', 'account_address_administrative_area_code', 'account_address_postal_code', 'account_address_locality', 'account_address_line1', 'account_address_line2'])]
#[Table(name: 'account_category_change_requests', keyType: 'string')]
class AccountCategoryChangeRequest extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    /** @return HasMany<AccountCategoryChangeRequestDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(AccountCategoryChangeRequestDocument::class, 'request_id');
    }

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejection_reason' => 'array',
        ];
    }
}
