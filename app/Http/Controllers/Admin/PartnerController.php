<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartnerStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Future partner organisations. Prototype only: no payments, no access to member data. */
class PartnerController extends Controller
{
    public function index(): View
    {
        return view('admin.partners', ['items' => Partner::latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Partner::TYPES)],
            'district' => ['nullable', 'string', 'max:40'],
            'plan' => ['nullable', 'string', 'max:80'],
            'contact_email' => ['nullable', 'email', 'max:150'],
        ]);
        $partner = Partner::create($data + ['status' => PartnerStatus::Prospective]);
        AuditLogger::log('partner.created', "Partner #{$partner->id} {$partner->name}", $partner);

        return back()->with('success', "{$partner->name} added.");
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(PartnerStatus::class)], 'reason' => ['required', 'string', 'max:500']]);
        $partner->update(['status' => $data['status']]);
        AuditLogger::log('partner.status_changed', "Partner #{$partner->id} {$partner->name} → {$partner->status->label()}", $partner, $data['reason']);

        return back()->with('success', 'Partner status updated.');
    }
}
