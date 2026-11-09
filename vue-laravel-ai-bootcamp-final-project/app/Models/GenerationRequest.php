<?php

namespace App\Models;

use App\Enums\ContentLanguage;
use App\Enums\ContentLength;
use App\Enums\ContentType;
use App\Enums\GenerationStatus;
use Database\Factories\GenerationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $idempotency_key
 * @property ContentType $content_type
 * @property ContentLanguage $language
 * @property ContentLength $length
 * @property int|null $built_in_tone_id
 * @property int|null $custom_tone_id
 * @property string $topic
 * @property string|null $audience
 * @property string|null $keywords
 * @property string|null $call_to_action
 * @property string|null $output_text
 * @property GenerationStatus $status
 * @property string|null $failure_reason
 * @property string|null $model
 * @property Carbon|null $stream_started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property int|null $regenerated_from_id
 */
#[Fillable([
    'user_id',
    'idempotency_key',
    'content_type',
    'language',
    'length',
    'built_in_tone_id',
    'custom_tone_id',
    'topic',
    'audience',
    'keywords',
    'call_to_action',
    'output_text',
    'status',
    'failure_reason',
    'model',
    'stream_started_at',
    'completed_at',
    'cancelled_at',
    'regenerated_from_id',
])]
class GenerationRequest extends Model
{
    /** @use HasFactory<GenerationRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'content_type' => ContentType::class,
            'language' => ContentLanguage::class,
            'length' => ContentLength::class,
            'status' => GenerationStatus::class,
            'stream_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<BuiltInTone, $this>
     */
    public function builtInTone(): BelongsTo
    {
        return $this->belongsTo(BuiltInTone::class);
    }

    /**
     * @return BelongsTo<CustomTone, $this>
     */
    public function customTone(): BelongsTo
    {
        return $this->belongsTo(CustomTone::class);
    }

    /**
     * @return HasOne<CreditReservation, $this>
     */
    public function reservation(): HasOne
    {
        return $this->hasOne(CreditReservation::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [GenerationStatus::Pending, GenerationStatus::Streaming], true);
    }
}
