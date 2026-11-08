<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PART 1 ONLY — placeholder for every form action.
 *
 * Nothing is validated or stored. The user is sent back with a clear message
 * saying what *will* happen once Part 2 connects the backend, so no button
 * pretends to work. Delete this controller when Part 2 routes are in place.
 */
class PreviewActionController extends Controller
{
    private const MESSAGES = [
        'login.attempt' => 'Sign-in is not connected yet. In Part 2 this will check your email/phone and password.',
        'admin.login.attempt' => 'Admin sign-in is not connected yet. In Part 2 only administrator accounts will get in.',
        'register.store' => 'Registration is not connected yet. In Part 2 your account will be created and verified.',
        'password.email' => 'Password reset is not connected yet. No email has been sent.',
        'password.update' => 'Password reset is not connected yet. Your password has not been changed.',
        'verification.verify' => 'Account verification is not connected yet. No code was checked.',
        'logout' => 'Sign-out will work once accounts exist (Part 2).',
        'contact.send' => 'The contact form is not connected yet. Your message has not been sent.',
        'listings.store' => 'Listing not saved. Saving listings and reviewer checks arrive in Part 2.',
        'listings.update' => 'Changes not saved. Editing listings arrives in Part 2.',
        'listings.status' => 'Status not changed. Listing status changes arrive in Part 2.',
        'requests.store' => 'Request not saved. Creating requests and matching arrive in Part 2.',
        'requests.update' => 'Changes not saved. Editing requests arrives in Part 2.',
        'requests.cancel' => 'Request not cancelled. This action arrives in Part 2.',
        'medicines.request' => 'Request not sent. Requesting a listing arrives in Part 2.',
        'saved.store' => 'Not saved. Saved medicines arrive in Part 2.',
        'saved.destroy' => 'Not removed. Saved medicines arrive in Part 2.',
        'reports.store' => 'Report not sent. Reporting and moderation arrive in Part 2.',
        'messages.send' => 'Message not sent. Private messaging arrives in Part 2.',
        'messages.start' => 'Chat not started. Private messaging arrives in Part 2.',
        'interactions.update' => 'Hand-over status not changed. This arrives in Part 2.',
        'notifications.read' => 'Not marked as read. Notifications are stored in the database from Part 2.',
        'notifications.read-all' => 'Not marked as read. Notifications are stored in the database from Part 2.',
        'verification.store' => 'Nothing uploaded. Secure private document upload arrives in Part 2.',
        'profile.update' => 'Profile not saved. Profile editing arrives in Part 2.',
        'settings.privacy.update' => 'Privacy settings not saved. This arrives in Part 2.',
        'settings.password.update' => 'Password not changed. This arrives in Part 2.',
        'settings.sessions.destroy' => 'No sessions were signed out. This arrives in Part 2.',
        'account.destroy' => 'Account not deleted. This arrives in Part 2.',
    ];

    public function __invoke(Request $request): RedirectResponse
    {
        $route = $request->route()?->getName() ?? '';
        $message = self::MESSAGES[$route]
            ?? (str_starts_with($route, 'admin.') ? 'Admin action not recorded. Moderation and audit logging arrive in Part 2.' : 'This action is not connected yet. It arrives in Part 2.');

        return back()->with('info', 'Preview: '.$message);
    }
}
