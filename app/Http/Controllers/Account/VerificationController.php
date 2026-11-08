<?php

namespace App\Http\Controllers\Account;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\VerificationSubmission;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The member's view: status and reviewer notes only. They can't re-download or share documents here. */
class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user();

        return view('account.verification', [
            'submissions' => $me->verificationSubmissions()->with(['request', 'reviews'])->latest()->get(),
            'requests' => $me->medicineRequests()->whereIn('status', RequestStatus::open())->latest()->get(),
            'selectedRequest' => $request->integer('request') ?: null,
        ]);
    }

    public function store(Request $request, VerificationService $service): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(VerificationSubmission::KINDS))],
            'request_id' => ['nullable', 'integer', Rule::exists('medicine_requests', 'id')->where('user_id', $request->user()->id)],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'consent' => ['accepted'],
        ]);
        $linked = $data['request_id'] ?? null ? $request->user()->medicineRequests()->find($data['request_id']) : null;
        $service->submit($request->user(), $data['kind'], $request->file('document'), $linked);

        return redirect()->route('verification.index')->with('success', 'Document submitted. An authorised reviewer will check it; you\'ll be notified of the decision.');
    }
}
