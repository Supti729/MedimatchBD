<x-layouts.app title="Messages">
    <x-page-header title="Messages" description="Private conversations. Phone numbers and emails are never shared." />
    <div class="card grid grid-cols-1 overflow-hidden lg:grid-cols-[20rem_1fr]">
        <div class="border-line lg:border-r">@include('account.messages._list')</div>
        <div class="hidden place-items-center p-10 text-center lg:grid">
            <div><x-icon name="message" class="mx-auto size-10 text-brand-600" /><p class="mt-3 font-bold">Select a conversation</p><p class="text-sm text-muted">Conversations start from a listing or a request.</p></div>
        </div>
    </div>
</x-layouts.app>
