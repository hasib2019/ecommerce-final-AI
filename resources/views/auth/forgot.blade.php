@extends('auth.layout')
@section('title', 'Forgot password?')
@section('subtitle', "Enter your account email and we'll send you a reset link.")
@section('form')
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label for="email" class="label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="input @error('email') input-error @enderror">
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <button class="btn btn-primary btn-lg w-full">Send reset link</button>
</form>
@endsection
@section('below')
<p class="mt-5 text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">← Back to sign in</a></p>
@endsection
