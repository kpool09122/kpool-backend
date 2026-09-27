<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable([
    'id',
    'category',
    'identity_identifier',
    'name',
    'email',
    'content',
    'language',
])]
#[Table(name: 'contacts', keyType: 'string')]
class Contact extends Model
{
    #[Override]
    public $incrementing = false;

    /**
     * @return HasMany<ContactReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class, 'contact_id');
    }
}
