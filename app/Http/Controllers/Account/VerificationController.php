<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(): View
    {
        return view('account.verification', [
            'submissions' => SampleData::verifications()->filter(fn ($v) => $v->user->id === SampleData::CURRENT_USER_ID),
            'requests' => SampleData::requests()->filter(fn ($r) => $r->owner->id === SampleData::CURRENT_USER_ID),
        ]);
    }
}
