<x-layouts.admin title="Users">
    <x-page-header title="User management" description="Search members, review their activity and change account status." />
    <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row">
        <label for="user-q" class="sr-only">Search users</label>
        <input id="user-q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Search by name, email or district" class="field mt-0 sm:max-w-sm">
        <label for="user-status" class="sr-only">Status</label>
        <select id="user-status" name="status" class="field mt-0 sm:w-48">
            <option value="">All statuses</option>
            @foreach (\App\Enums\AccountStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach
        </select>
        <x-button type="submit" icon="search">Search</x-button>
    </form>
    @if ($users->isEmpty())
        <x-empty-state title="No users match these filters" icon="users" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Users</caption>
                <thead><tr><th scope="col">Member</th><th scope="col">Role</th><th scope="col">Area</th><th scope="col">Status</th><th scope="col">Joined</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><div class="flex items-center gap-3"><x-avatar :name="$u->name" size="sm" /><span><span class="font-semibold">{{ $u->name }}</span><span class="block text-xs text-muted">{{ $u->email }}</span></span></div></td>
                            <td class="capitalize">{{ $u->role }}</td>
                            <td class="whitespace-nowrap">{{ $u->district }}</td>
                            <td><x-status-badge :status="$u->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $u->joined_at->format('d M Y') }}</td>
                            <td class="text-right"><x-button :href="route('admin.users.show', $u->id)" variant="neutral" size="sm">View</x-button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
