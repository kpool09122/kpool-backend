<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $account_id
 * @property string $document_type
 * @property string $document_path
 * @property Carbon $uploaded_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'account_id',
    'document_type',
    'document_path',
    'uploaded_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_documents')]
class AccountDocument extends Model
{
    public $timestamps = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
