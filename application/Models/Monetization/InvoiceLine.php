<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $invoice_id
 * @property string $description
 * @property string $currency
 * @property int $unit_price
 * @property int $quantity
 * @property \Illuminate\Support\Carbon $created_at
 */
class InvoiceLine extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'invoice_lines';

    protected $fillable = [
        'invoice_id',
        'description',
        'currency',
        'unit_price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
