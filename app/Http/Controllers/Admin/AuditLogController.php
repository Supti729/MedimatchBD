<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $action = (string) $request->query('action');

        return view('admin.audit-logs', [
            'items' => AuditLog::with('actor')->when($action, fn ($q) => $q->where('action', 'like', $action.'%'))->latest('created_at')->latest('id')->paginate(30)->withQueryString(),
            'action' => $action,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action')->map(fn ($a) => strtok($a, '.'))->unique()->values(),
        ]);
    }
}
