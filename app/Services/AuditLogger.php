<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Records administrative and reviewer actions in the append-only audit log. */
class AuditLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, ?string $reason = null, array $meta = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 255),
            'reason' => $reason,
            'ip_address' => request()?->ip(),
            'meta' => $meta ?: null,
        ]);
    }
}
