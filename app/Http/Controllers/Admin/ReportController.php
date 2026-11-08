<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', ['reports' => SampleData::reports()->sortByDesc('created_at')]);
    }

    public function show(int $report): View
    {
        $report = SampleData::report($report);
        abort_unless($report, 404);

        return view('admin.reports.show', ['report' => $report]);
    }
}
