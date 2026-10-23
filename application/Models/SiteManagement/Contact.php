<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Override;

#[Fillable([
    'id',
    'category',
    'principal_identifier',
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

    /**
     * A successful operator reply after every submitter post resolves the inquiry.
     * Compare (display timestamp, reply ID); failed submitter notifications still count.
     *
     * @return HasMany<ContactReply, $this>
     */
    public function resolvingReplies(): HasMany
    {
        return $this->replies()
            ->where('reply_type', 'operator')
            ->whereNotNull('sent_at')
            ->whereNull('failed_at')
            ->whereNotExists(static fn (Builder $query) => $query->selectRaw('1')
                ->from('contact_replies as submitter_replies')
                ->whereColumn('submitter_replies.contact_id', 'contact_replies.contact_id')
                ->where('submitter_replies.reply_type', 'submitter')
                ->where(static fn (Builder $query) => $query
                    ->whereColumn('submitter_replies.created_at', '>', 'contact_replies.sent_at')
                    ->orWhere(static fn (Builder $query) => $query
                        ->whereColumn('submitter_replies.created_at', '=', 'contact_replies.sent_at')
                        ->whereColumn('submitter_replies.id', '>', 'contact_replies.id'))));
    }
}
