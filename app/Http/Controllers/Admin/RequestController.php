<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\MedicineRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = RequestStatus::tryFrom((string) $request->query('status'));

        return view('admin.requests', [
            'requests' => MedicineRequest::with(['owner', 'location'])->withCount('matches')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }
}
