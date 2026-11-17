<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $stripe_event_id
 * @property string $event_type
 * @property int|null $payment_order_id
 * @property array<string, mixed>|null $payload
 * @property Carbon $processed_at
 */
#[Fillable([
    'stripe_event_id',
    'event_type',
    'payment_order_id',
    'payload',
    'processed_at',
])]
class PaymentWebhookEvent extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PaymentOrder, $this>
     */
    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }
}
