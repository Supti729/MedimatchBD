<x-layouts.admin title="Users">
    <x-page-header title="User management" description="Search members, review their activity and change account status." />
    <form method="GET" class="mb-4 grid gap-3 sm:grid-cols-[1fr_12rem_12rem_auto]">
        <label for="user-q" class="sr-only">Search users</label>
        <input id="user-q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Name, email, phone or district" class="field mt-0">
        <label for="user-status" class="sr-only">Status</label>
        <select id="user-status" name="status" class="field mt-0">
            <option value="">All statuses</option>
            @foreach (\App\Enums\AccountStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach
        </select>
        <label for="user-role" class="sr-only">Role</label>
        <select id="user-role" name="role" class="field mt-0">
            <option value="">All roles</option>
            @foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected($role === $r)>{{ $r->label() }}</option>@endforeach
        </select>
        <x-button type="submit" icon="search">Search</x-button>
    </form>
    @if ($users->isEmpty())
        <x-empty-state title="No users match these filters" icon="users" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Users</caption>
                <thead><tr><th scope="col">Member</th><th scope="col">Role</th><th scope="col">District</th><th scope="col">Status</th><th scope="col">Joined</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><div class="flex items-center gap-3"><x-avatar :user="$u" size="sm" /><span><span class="font-semibold">{{ $u->name }}</span><span class="block text-xs text-muted">{{ $u->email ?? $u->phone_display }}</span></span></div></td>
                            <td>{{ $u->role->label() }}</td>
                            <td class="whitespace-nowrap">{{ $u->district ?? '—' }}</td>
                            <td><x-status-badge :status="$u->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $u->created_at->format('d M Y') }}</td>
                            <td class="text-right"><x-button :href="route('admin.users.show', $u)" variant="neutral" size="sm">View</x-button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    @endif
</x-layouts.admin>
