<?php

namespace App\Http\Controllers;

use App\Models\MedicineListing;
use Illuminate\View\View;

/** Public information pages. */
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'recent' => MedicineListing::publiclyVisible()->withCardData()->latest('published_at')->limit(3)->get(),
        ]);
    }

    public function howItWorks(): View
    {
        return view('pages.how-it-works');
    }

    public function safety(): View
    {
        return view('pages.safety');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }
}
