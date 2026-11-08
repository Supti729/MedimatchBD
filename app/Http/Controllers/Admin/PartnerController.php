<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.partners', ['items' => SampleData::partners()]);
    }
}
