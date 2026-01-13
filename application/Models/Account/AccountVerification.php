<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $verification_type
 * @property string $status
 * @property array $applicant_info
 * @property Carbon $requested_at
 * @property ?string $reviewed_by
 * @property ?Carbon $reviewed_at
 * @property ?array $rejection_reason
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, VerificationDocument> $documents
 */
class AccountVerification extends Model
{
    public $incrementing = false;

    protected $table = 'account_verifications';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'verification_type',
        'status',
        'applicant_info',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'applicant_info' => 'array',
            'rejection_reason' => 'array',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<VerificationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'verification_id', 'id');
    }
}
