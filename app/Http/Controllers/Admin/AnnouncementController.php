<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.announcements', ['items' => SampleData::announcements()]);
    }
}
