@extends('auth.layout')
@section('title', 'Welcome back')
@section('subtitle', 'Sign in to track orders and check out faster.')
@section('form')
<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label for="login" class="label">Mobile number or email</label>
        <input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="input @error('login') input-error @enderror" placeholder="01XXXXXXXXX">
        @error('login')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div x-data="{ show: false }">
        <div class="flex items-center justify-between"><label for="password" class="label">Password</label><a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-semibold text-primary-600 hover:underline">Forgot?</a></div>
        <div class="relative">
            <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" class="input pr-12">
            <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-slate-500" x-text="show ? 'Hide' : 'Show'">Show</button>
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" checked class="size-4 accent-[var(--brand)]"> Keep me signed in</label>
    <button class="btn btn-primary btn-lg w-full">Sign in</button>
</form>
@endsection
@section('below')
<p class="mt-5 text-center text-sm text-slate-600">New here? <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:underline">Create an account</a></p>
@endsection
