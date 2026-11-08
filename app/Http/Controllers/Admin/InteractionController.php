<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class InteractionController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.interactions', ['items' => SampleData::interactions()]);
    }
}
