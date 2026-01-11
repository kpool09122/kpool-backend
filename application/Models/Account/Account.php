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
 * @property array $contract_info
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Account extends Model
{
    public $incrementing = false;

    protected $table = 'accounts';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'email',
        'type',
        'name',
        'status',
        'category',
        'contract_info',
    ];

    protected function casts(): array
    {
        return [
            'contract_info' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
