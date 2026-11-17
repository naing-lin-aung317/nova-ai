<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $actor_user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 */
#[Fillable([
    'actor_user_id',
    'action',
    'subject_type',
    'subject_id',
    'reason',
    'metadata',
    'ip_address',
])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
