<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

/** Verified medicine catalogue and eligibility rules. */
class MedicineController extends Controller
{
    public function __invoke(): View
    {
        $counts = SampleData::listings()->groupBy(fn ($l) => $l->medicine->id)->map->count();

        return view('admin.medicines', ['medicines' => SampleData::catalogue(), 'counts' => $counts]);
    }
}
