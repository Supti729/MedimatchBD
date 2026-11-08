<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MedicineEligibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineRequestRequest;
use App\Models\Medicine;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Verified medicine catalogue and its eligibility rules. */
class MedicineController extends Controller
{
    public function index(): View
    {
        return view('admin.medicines', ['medicines' => Medicine::with('aliases')->withCount('listings')->orderBy('brand_name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:100', Rule::unique('medicines')->where(fn ($q) => $q->where('strength', $request->input('strength'))->where('dosage_form', $request->input('dosage_form')))],
            'generic_name' => ['required', 'string', 'max:100'],
            'strength' => ['required', 'string', 'max:30'],
            'dosage_form' => ['required', Rule::in(MedicineRequestRequest::FORMS)],
            'eligibility' => ['required', Rule::enum(MedicineEligibility::class)],
            'aliases' => ['nullable', 'string', 'max:500'],
        ], ['brand_name.unique' => 'This brand, strength and form is already in the catalogue.']);

        $medicine = Medicine::create($data + ['is_active' => true]);
        $this->syncAliases($medicine, $data['aliases'] ?? '');
        AuditLogger::log('catalogue.created', "Medicine #{$medicine->id} {$medicine->brand_name}", $medicine, null, ['eligibility' => $medicine->eligibility->value]);

        return back()->with('success', "{$medicine->brand_name} added to the catalogue.");
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $data = $request->validate([
            'eligibility' => ['required', Rule::enum(MedicineEligibility::class)],
            'is_active' => ['required', 'boolean'],
            'aliases' => ['nullable', 'string', 'max:500'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $from = $medicine->eligibility;
        $medicine->update(['eligibility' => $data['eligibility'], 'is_active' => $data['is_active']]);
        $this->syncAliases($medicine, $data['aliases'] ?? '');
        AuditLogger::log('catalogue.updated', "Medicine #{$medicine->id} {$medicine->brand_name}: {$from->label()} → {$medicine->eligibility->label()}", $medicine, $data['reason']);

        return back()->with('success', "{$medicine->brand_name} updated.");
    }

    private function syncAliases(Medicine $medicine, string $aliases): void
    {
        $list = collect(explode(',', $aliases))->map(fn ($a) => trim($a))->filter()->unique()->take(20);
        $medicine->aliases()->whereNotIn('alias', $list)->delete();
        $list->each(fn ($a) => $medicine->aliases()->firstOrCreate(['alias' => mb_substr($a, 0, 100)]));
    }
}
