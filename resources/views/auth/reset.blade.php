@extends('auth.layout')
@section('title', 'Set a new password')
@section('subtitle', 'Choose a strong password you have not used before.')
@section('form')
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label for="email" class="label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="input @error('email') input-error @enderror">
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="label">New password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password" class="input @error('password') input-error @enderror">
        @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="label">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required class="input">
    </div>
    <button class="btn btn-primary btn-lg w-full">Reset password</button>
</form>
@endsection
