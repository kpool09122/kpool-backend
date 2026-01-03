<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $order_id
 * @property string $customer_id
 * @property string $currency
 * @property int $subtotal
 * @property int $discount_amount
 * @property int $tax_amount
 * @property int $total
 * @property \Illuminate\Support\Carbon $issued_at
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $status
 * @property ?string $tax_document_type
 * @property ?string $tax_document_country
 * @property ?string $tax_document_registration_number
 * @property ?\Illuminate\Support\Carbon $tax_document_issue_deadline
 * @property ?string $tax_document_reason
 * @property ?\Illuminate\Support\Carbon $paid_at
 * @property ?\Illuminate\Support\Carbon $voided_at
 * @property ?string $void_reason
 */
class Invoice extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'invoices';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'customer_id',
        'currency',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'issued_at',
        'due_date',
        'status',
        'tax_document_type',
        'tax_document_country',
        'tax_document_registration_number',
        'tax_document_issue_deadline',
        'tax_document_reason',
        'paid_at',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'issued_at' => 'datetime',
            'due_date' => 'datetime',
            'tax_document_issue_deadline' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id', 'id');
    }
}
