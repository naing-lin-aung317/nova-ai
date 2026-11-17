<?php

namespace App\Models;

use App\Enums\PaymentOrderStatus;
use Database\Factories\PaymentOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $credit_package_id
 * @property int $amount_cents
 * @property string $currency
 * @property int $credits_to_issue
 * @property PaymentOrderStatus $status
 * @property string|null $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property int|null $credit_transaction_id
 * @property Carbon|null $paid_at
 */
#[Fillable([
    'user_id',
    'credit_package_id',
    'amount_cents',
    'currency',
    'credits_to_issue',
    'status',
    'stripe_checkout_session_id',
    'stripe_payment_intent_id',
    'credit_transaction_id',
    'paid_at',
    'fulfilled_by_admin_at',
    'fulfilled_by_user_id',
])]
class PaymentOrder extends Model
{
    /** @use HasFactory<PaymentOrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PaymentOrderStatus::class,
            'paid_at' => 'datetime',
            'fulfilled_by_admin_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CreditPackage, $this>
     */
    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }

    /**
     * @return BelongsTo<CreditTransaction, $this>
     */
    public function creditTransaction(): BelongsTo
    {
        return $this->belongsTo(CreditTransaction::class);
    }

    public function amountUsd(): string
    {
        return number_format($this->amount_cents / 100, 2, '.', '');
    }
}
