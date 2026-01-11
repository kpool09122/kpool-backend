<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $resource_id
 * @property string $owner_account_id
 * @property string $status
 * @property Carbon $requested_at
 * @property ?Carbon $approved_at
 * @property ?Carbon $rejected_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class OfficialCertification extends Model
{
    public $incrementing = false;

    protected $table = 'official_certifications';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'resource_type',
        'resource_id',
        'owner_account_id',
        'status',
        'requested_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
