<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $account_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'identity_id',
    'account_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_principals', keyType: 'string')]
class Principal extends Model
{
    public $incrementing = false;
}
