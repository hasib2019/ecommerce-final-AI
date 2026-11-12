@if(session('success') || session('error') || session('status'))
    <div x-data x-init="$store.toast.show(@js(session('success') ?? session('status') ?? session('error')), @js(session('error') ? 'error' : 'success'))"></div>
@endif
