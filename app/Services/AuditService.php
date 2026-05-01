<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(
        string  $action,
        string  $description,
        ?object $subject = null,
        ?int    $userId = null
    ): void {
        AuditLog::create([
            'user_id'      => $userId ?? Auth::id(),
            'action'       => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id'   => $subject?->id,
            'description'  => $description,
            'ip_address'   => Request::ip(),
            'user_agent'   => substr(Request::userAgent() ?? '', 0, 255),
        ]);
    }
}
