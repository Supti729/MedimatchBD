<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $me = $request->user();

        return view('account.history', [
            'interactions' => Interaction::involving($me)->with(['listing.medicine', 'donor', 'requester'])->latest('updated_at')->paginate(20)
                ->through(function ($i) use ($me) {
                    $i->setAttribute('direction', $i->donor_id === $me->id ? 'given' : 'received');
                    $i->setRelation('other', $i->donor_id === $me->id ? $i->requester : $i->donor);

                    return $i;
                }),
        ]);
    }
}
