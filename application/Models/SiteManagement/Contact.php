<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'category',
    'identity_identifier',
    'name',
    'email',
    'content',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'contacts', keyType: 'string')]
class Contact extends Model
{
    #[\Override]
    public $incrementing = false;
}
