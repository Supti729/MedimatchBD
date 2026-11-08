<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

/** Part 2: restricted to the "reviewer" permission, every view of a document is audit-logged. */
class VerificationController extends Controller
{
    public function index(): View
    {
        return view('admin.verifications.index', ['submissions' => SampleData::verifications()->sortByDesc('submitted_at')]);
    }

    public function show(int $verification): View
    {
        $verification = SampleData::verification($verification);
        abort_unless($verification, 404);

        return view('admin.verifications.show', ['submission' => $verification]);
    }
}
