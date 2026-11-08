<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.requests', ['requests' => SampleData::requests()->sortByDesc('created_at')]);
    }
}
