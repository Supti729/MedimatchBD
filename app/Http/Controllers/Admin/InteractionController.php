<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InteractionStatus;
use App\Http\Controllers\Controller;
use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Status monitoring only — the platform never handles payments. */
class InteractionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = InteractionStatus::tryFrom((string) $request->query('status'));

        return view('admin.interactions', [
            'items' => Interaction::with(['listing.medicine', 'donor', 'requester'])->when($status, fn ($q) => $q->where('status', $status))->latest('updated_at')->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }
}
