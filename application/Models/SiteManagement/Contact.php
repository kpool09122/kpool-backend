<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'category',
    'identity_identifier',
    'name',
    'email',
    'content',
    'language',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'contacts', keyType: 'string')]
class Contact extends Model
{
    #[\Override]
    public $incrementing = false;

    /**
     * @return HasMany<ContactReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class, 'contact_id');
    }
}
