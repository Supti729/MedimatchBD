<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(): View
    {
        return view('account.history', ['interactions' => SampleData::interactions()->sortByDesc('updated_at')]);
    }
}
