<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('account.saved', [
            'listings' => $request->user()->savedListings()->with(['medicine.aliases', 'location', 'owner'])->orderByPivot('created_at', 'desc')->get(),
        ]);
    }
}
