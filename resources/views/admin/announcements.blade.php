<x-layouts.admin title="Announcements">
    <x-page-header title="Notifications management" description="Send platform announcements to members as in-app notifications." />
    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <x-section title="Sent announcements" :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($items as $a)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <span class="min-w-0"><span class="font-semibold">{{ $a->title }}</span><span class="block text-sm text-muted">{{ $a->audience_label }} · {{ $a->recipients_count }} {{ Str::plural('recipient', $a->recipients_count) }} · {{ $a->created_at->format('d M Y') }} · {{ $a->author?->name }}</span></span>
                        <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-100 text-green-800' => $a->sent, 'bg-slate-100 text-slate-600' => ! $a->sent])>{{ $a->sent ? '✓ Sent' : '✎ Not sent' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">No announcements yet.</li>
                @endforelse
            </ul>
        </x-section>
        <x-section title="New announcement">
            <form method="POST" action="{{ route('admin.announcements.store') }}" data-loading class="space-y-4" data-location-group>
                @csrf
                <x-form.input name="title" label="Title" required maxlength="120" />
                <x-form.textarea name="body" label="Message" required rows="4" maxlength="500" />
                <x-form.select name="audience" label="Audience" required :options="\App\Models\Announcement::AUDIENCES" />
                <x-form.select name="audience_division" label="Division" placeholder="Only for “Members in one division”" :options="$divisions" />
                <x-button type="submit" icon="megaphone" class="w-full" data-loading-text="Sending…">Send announcement</x-button>
            </form>
        </x-section>
    </div>
</x-layouts.admin>
