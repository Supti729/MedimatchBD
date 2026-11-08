<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('pages.contact');
    }

    /** Sends the message to the team address through the configured mailer ("log" in development). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'topic' => ['required', 'in:general,safety,partnership,privacy,technical'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        Mail::raw("From: {$data['name']} <{$data['email']}>\nTopic: {$data['topic']}\n\n{$data['message']}", function ($mail) use ($data) {
            $mail->to(config('mail.from.address'))->replyTo($data['email'], $data['name'])->subject("[Medimatch BD contact] {$data['topic']}");
        });

        return back()->with('success', 'Thank you. Your message has been sent to the team.')
            ->with('info', config('mail.default') === 'log' ? 'Development mode: the message was written to storage/logs/laravel.log, not emailed.' : null);
    }
}
