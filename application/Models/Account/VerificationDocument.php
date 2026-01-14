<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $verification_id
 * @property string $document_type
 * @property string $document_path
 * @property string $original_file_name
 * @property int $file_size_bytes
 * @property Carbon $uploaded_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class VerificationDocument extends Model
{
    public $incrementing = false;

    protected $table = 'verification_documents';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'verification_id',
        'document_type',
        'document_path',
        'original_file_name',
        'file_size_bytes',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(AccountVerification::class, 'verification_id', 'id');
    }
}
