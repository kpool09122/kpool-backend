<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $request_id
 * @property string $document_type
 * @property string $document_path
 * @property Carbon $uploaded_at
 */
#[Fillable(['request_id', 'document_type', 'document_path', 'uploaded_at'])]
#[Table(name: 'account_category_change_request_documents')]
class AccountCategoryChangeRequestDocument extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    /** @return BelongsTo<AccountCategoryChangeRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AccountCategoryChangeRequest::class, 'request_id');
    }
}
