<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class SavedController extends Controller
{
    public function __invoke(): View
    {
        return view('account.saved', ['listings' => SampleData::publicListings()->only([1, 5, 12])]);
    }
}
