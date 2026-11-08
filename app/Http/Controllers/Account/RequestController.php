<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

/** The member's own medicine requests. */
class RequestController extends Controller
{
    public function index(): View
    {
        return view('account.requests.index', [
            'requests' => SampleData::requests()->filter(fn ($r) => $r->owner->id === SampleData::CURRENT_USER_ID)->sortByDesc('created_at'),
        ]);
    }

    public function create(): View
    {
        return view('account.requests.form', ['medicineRequest' => null, 'catalogue' => SampleData::catalogue()]);
    }

    public function show(int $medicineRequest): View
    {
        return view('account.requests.show', ['medicineRequest' => $this->own($medicineRequest)]);
    }

    public function edit(int $medicineRequest): View
    {
        return view('account.requests.form', ['medicineRequest' => $this->own($medicineRequest), 'catalogue' => SampleData::catalogue()]);
    }

    private function own(int $id): object
    {
        $request = SampleData::request($id);
        abort_unless($request && $request->owner->id === SampleData::CURRENT_USER_ID, 404);

        return $request;
    }
}
