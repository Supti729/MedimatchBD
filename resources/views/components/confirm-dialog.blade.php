{{-- Confirmation modal. Open it with: <button data-dialog-open="{{ $id }}">…</button> --}}
@props(['id', 'title', 'action', 'method' => 'POST', 'confirm' => 'Confirm', 'variant' => 'primary', 'reason' => false, 'reasonLabel' => 'Reason (recorded in the audit log)'])
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 text-ink shadow-pop">
    <form method="POST" action="{{ $action }}" data-loading class="p-6">
        @csrf
        @if (strtoupper($method) !== 'POST') @method($method) @endif
        <h2 id="{{ $id }}-title" class="text-lg font-bold">{{ $title }}</h2>
        <div class="mt-2 text-sm text-muted">{{ $slot }}</div>
        @if ($reason)
            <label for="{{ $id }}-reason" class="label mt-4">{{ $reasonLabel }}</label>
            <textarea id="{{ $id }}-reason" name="reason" rows="3" required maxlength="500" class="field"></textarea>
        @endif
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button variant="neutral" data-dialog-close>Cancel</x-button>
            <x-button type="submit" :variant="$variant" data-loading-text="Working…">{{ $confirm }}</x-button>
        </div>
    </form>
</dialog>
