<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\VerificationSubmission;
use App\Services\AuditLogger;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Restricted to authorised reviewers (route: can:review-verifications). Every document view is audit-logged. */
class VerificationController extends Controller
{
    public function __construct(private VerificationService $service) {}

    public function index(Request $request): View
    {
        $status = VerificationStatus::tryFrom((string) $request->query('status'));

        return view('admin.verifications.index', [
            'submissions' => VerificationSubmission::with(['user', 'request'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("CASE status WHEN 'submitted' THEN 0 WHEN 'under_review' THEN 1 ELSE 2 END")->latest()
                ->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Request $request, VerificationSubmission $verification): View
    {
        Gate::authorize('viewDocument', $verification);
        $this->service->startReview($verification, $request->user());

        return view('admin.verifications.show', ['submission' => $verification->load(['user', 'request', 'reviews.reviewer'])]);
    }

    /** Streams the private file inline. Never cached, never given a public URL. */
    public function document(VerificationSubmission $verification): StreamedResponse
    {
        Gate::authorize('viewDocument', $verification);
        AuditLogger::log('verification.document_viewed', "Opened document for verification #{$verification->id}", $verification);

        return Storage::disk(VerificationService::DISK)->response($verification->document_path, 'verification-'.$verification->id, [
            'Content-Type' => $verification->mime_type,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
        ], 'inline');
    }

    public function decide(Request $request, VerificationSubmission $verification): RedirectResponse
    {
        Gate::authorize('decide', $verification);
        $data = $request->validate([
            'decision' => ['required', Rule::in([VerificationStatus::Verified->value, VerificationStatus::Rejected->value, VerificationStatus::ReuploadRequired->value])],
            'note' => ['required', 'string', 'max:500'],
        ]);
        $this->service->decide($verification, $request->user(), VerificationStatus::from($data['decision']), $data['note']);

        return redirect()->route('admin.verifications.index')->with('success', 'Decision recorded. The member was notified.');
    }
}
