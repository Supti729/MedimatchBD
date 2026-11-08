<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Ai\SemanticRanker;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(SemanticRanker $ai): View
    {
        return view('admin.settings', [
            'settings' => collect(Setting::DEFAULTS)->map(fn ($v, $k) => Setting::get($k))->all(),
            'aiHealth' => $ai->health(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'min_expiry_months' => ['required', 'integer', 'min:1', 'max:12'],
            'request_expiry_days' => ['required', 'integer', 'min:7', 'max:90'],
        ]);
        $values = [
            'review_new_members' => $request->boolean('review_new_members'),
            'review_verification_required' => $request->boolean('review_verification_required'),
            'allow_affordable_access' => $request->boolean('allow_affordable_access'),
            'ai_matching' => $request->boolean('ai_matching'),
            'min_expiry_months' => (int) $data['min_expiry_months'],
            'request_expiry_days' => (int) $data['request_expiry_days'],
        ];
        $changed = collect($values)->filter(fn ($v, $k) => Setting::get($k) !== $v);
        Setting::put($values);
        if ($changed->isNotEmpty()) {
            AuditLogger::log('settings.updated', 'Changed: '.$changed->keys()->implode(', '), null, null, $changed->all());
        }

        return back()->with('success', 'Settings saved.');
    }
}
