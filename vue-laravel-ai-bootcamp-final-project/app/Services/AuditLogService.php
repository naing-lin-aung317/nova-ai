<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        User $actor,
        string $action,
        ?Model $subject = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'reason' => $reason,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
        ]);
    }
}
