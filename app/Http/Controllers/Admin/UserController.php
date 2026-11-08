<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\UserModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private UserModerationService $moderation) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $status = AccountStatus::tryFrom((string) $request->query('status'));
        $role = Role::tryFrom((string) $request->query('role'));

        return view('admin.users.index', [
            'users' => User::with('location')
                ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', '%'.preg_replace('/\D/', '', $q).'%')
                    ->orWhereHas('location', fn ($l) => $l->where('district', 'like', "%{$q}%"))))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($role, fn ($query) => $query->where('role', $role))
                ->latest()->paginate(20)->withQueryString(),
            'q' => $q,
            'status' => $status,
            'role' => $role,
        ]);
    }

    public function show(User $user): View
    {
        AuditLogger::log('user.viewed', "Viewed private details of user #{$user->id}", $user);

        return view('admin.users.show', [
            'user' => $user->load('location'),
            'listings' => $user->listings()->with('medicine')->latest()->get(),
            'requests' => $user->medicineRequests()->latest()->get(),
            'reports' => Report::where('reported_user_id', $user->id)->latest()->get(),
        ]);
    }

    public function status(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AccountStatus::class)],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $this->moderation->changeStatus($user, AccountStatus::from($data['status']), $data['reason']);

        return back()->with('success', "{$user->name} is now ".strtolower(AccountStatus::from($data['status'])->label()).'.');
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $this->moderation->changeRole($user, Role::from($data['role']), $data['reason']);

        return back()->with('success', "{$user->name}'s role was changed to ".Role::from($data['role'])->label().'.');
    }
}
