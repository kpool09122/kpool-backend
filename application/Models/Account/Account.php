<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $email
 * @property string $type
 * @property string $name
 * @property string $status
 * @property string $category
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'email',
    'type',
    'name',
    'status',
    'category',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'accounts', keyType: 'string')]
class Account extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
