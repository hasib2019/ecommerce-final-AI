@extends('layouts.app')

@section('content')
@include('partials.breadcrumbs')
<div class="container-x pb-12 pt-4">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Contact us</h1>
    <p class="mt-1 text-slate-500">We're here to help — usually we reply within a few hours.</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
        <form method="POST" action="{{ route('contact.store') }}" class="card grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
            @csrf
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div><label for="c-name" class="label">Your name *</label><input id="c-name" name="name" value="{{ old('name') }}" required class="input @error('name') input-error @enderror">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label for="c-phone" class="label">Phone</label><input id="c-phone" name="phone" type="tel" value="{{ old('phone') }}" class="input @error('phone') input-error @enderror">@error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label for="c-email" class="label">Email</label><input id="c-email" name="email" type="email" value="{{ old('email') }}" class="input @error('email') input-error @enderror">@error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label for="c-subject" class="label">Subject</label><input id="c-subject" name="subject" value="{{ old('subject') }}" class="input"></div>
            <div class="sm:col-span-2"><label for="c-message" class="label">Message *</label><textarea id="c-message" name="message" rows="5" required class="input @error('message') input-error @enderror">{{ old('message') }}</textarea>@error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><button class="btn btn-primary">Send message</button></div>
        </form>
        <aside class="space-y-3">
            @foreach([['phone', 'Call us', setting('general.phone'), 'tel:'.preg_replace('/[^\d+]/', '', (string) setting('general.phone'))], ['whatsapp', 'WhatsApp', setting('general.whatsapp') ? '+'.preg_replace('/\D/', '', setting('general.whatsapp')) : null, 'https://wa.me/'.preg_replace('/\D/', '', (string) setting('general.whatsapp'))], ['mail', 'Email', setting('general.email'), 'mailto:'.setting('general.email')], ['map-pin', 'Visit us', setting('general.address'), null], ['clock', 'Opening hours', setting('general.opening_hours'), null]] as [$ic, $label, $value, $href])
                @if($value)
                    <div class="card flex items-start gap-3 p-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary-50 text-primary-600">{{ icon($ic, 'size-5') }}</span>
                        <div><p class="text-sm font-semibold text-slate-900">{{ $label }}</p>@if($href)<a href="{{ $href }}" class="text-sm text-slate-600 hover:text-primary-600">{{ $value }}</a>@else<p class="text-sm text-slate-600">{{ $value }}</p>@endif</div>
                    </div>
                @endif
            @endforeach
        </aside>
    </div>
</div>
@endsection
