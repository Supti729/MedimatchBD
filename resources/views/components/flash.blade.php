{{-- Session flash messages. Controllers set: ->with('success'|'error'|'warning'|'info', 'Message') --}}
@foreach (['success', 'error', 'warning', 'info'] as $type)
    @if (session($type))
        <x-alert :type="$type" dismissible class="mb-5">{{ session($type) }}</x-alert>
    @endif
@endforeach
@if (isset($errors) && $errors->any())
    <x-alert type="error" title="Please check the highlighted fields." class="mb-5">
        <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </x-alert>
@endif
