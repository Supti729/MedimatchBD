<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = ReportStatus::tryFrom((string) $request->query('status'));

        return view('admin.reports.index', [
            'reports' => Report::with(['reporter', 'reportedUser', 'reportable'])->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("CASE status WHEN 'escalated' THEN 0 WHEN 'open' THEN 1 WHEN 'under_review' THEN 2 ELSE 3 END")->latest()
                ->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Report $report): View
    {
        $report->load(['reporter', 'reportedUser', 'handler', 'reportable']);
        $messages = $report->reportable instanceof Conversation
            ? $report->reportable->messages()->with('sender')->latest()->limit(30)->get()->reverse()
            : collect();

        return view('admin.reports.show', ['report' => $report, 'messages' => $messages]);
    }

    public function decide(Request $request, Report $report, ReportService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['resolved', 'rejected', 'escalated', 'suspend', 'block_listing'])],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        $service->decide($report, $request->user(), $data['decision'], $data['note']);

        return back()->with('success', 'Decision recorded. The reporter was notified.');
    }
}
