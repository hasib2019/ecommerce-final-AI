@extends('auth.layout')
@section('title', 'Create your account')
@section('subtitle', 'Faster checkout, order history and exclusive offers.')
@section('form')
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div>
        <label for="name" class="label">Full name</label>
        <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="input @error('name') input-error @enderror">
        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="label">Mobile number</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" placeholder="01XXXXXXXXX" class="input @error('phone') input-error @enderror">
        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="label">Email <span class="font-normal text-slate-400">(optional)</span></label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" class="input @error('email') input-error @enderror">
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="input @error('password') input-error @enderror">
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input">
        </div>
        @error('password')<p class="-mt-2 text-xs text-rose-600 sm:col-span-2">{{ $message }}</p>@enderror
    </div>
    <button class="btn btn-primary btn-lg w-full">Create account</button>
</form>
@endsection
@section('below')
<p class="mt-5 text-center text-sm text-slate-600">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Sign in</a></p>
@endsection
