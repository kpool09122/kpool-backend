<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $image_id
 * @property string $requester_name
 * @property string $requester_email
 * @property string $reason
 * @property string $status
 * @property Carbon $requested_at
 * @property ?string $reviewer_id
 * @property ?Carbon $reviewed_at
 * @property ?string $reviewer_comment
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'image_id',
    'requester_name',
    'requester_email',
    'reason',
    'status',
    'requested_at',
    'reviewer_id',
    'reviewed_at',
    'reviewer_comment',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'image_hide_requests', keyType: 'string')]
class ImageHideRequest extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
