<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function privacy(): View
    {
        return view('account.settings.privacy', ['me' => SampleData::currentUser()]);
    }

    public function security(): View
    {
        return view('account.settings.security', ['me' => SampleData::currentUser()]);
    }
}
