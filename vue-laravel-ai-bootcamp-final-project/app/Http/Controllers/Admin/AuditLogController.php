<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->paginate(30)
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->actor?->name,
                'actor_email' => $log->actor?->email,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'reason' => $log->reason,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/AuditLog', [
            'logs' => $logs,
        ]);
    }
}
