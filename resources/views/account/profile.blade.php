@extends('account.layout')

@section('account')
<h1 class="mb-5 text-2xl font-extrabold text-slate-900">Profile & security</h1>
<div class="grid gap-5 xl:grid-cols-2">
    <form method="POST" action="{{ route('account.profile.update') }}" class="card space-y-4 p-5">
        @csrf @method('PUT')
        <h2 class="font-bold text-slate-900">Personal information</h2>
        <div><label for="p-name" class="label">Full name</label><input id="p-name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') input-error @enderror">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="p-phone" class="label">Mobile</label><input id="p-phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required class="input @error('phone') input-error @enderror">@error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label for="p-email" class="label">Email</label><input id="p-email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input @error('email') input-error @enderror">@error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
        </div>
        <div><label for="p-address" class="label">Default address</label><input id="p-address" name="address" value="{{ old('address', $user->address) }}" class="input"></div>
        <div><label for="p-city" class="label">District / City</label>
            <select id="p-city" name="city" class="input">
                <option value="">Select…</option>
                @foreach(config('shop.districts') as $d)<option value="{{ $d }}" @selected(old('city', $user->city) === $d)>{{ $d }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary">Save changes</button>
    </form>
    <form method="POST" action="{{ route('account.password.update') }}" class="card space-y-4 self-start p-5">
        @csrf @method('PUT')
        <h2 class="font-bold text-slate-900">Change password</h2>
        <div><label for="cp" class="label">Current password</label><input id="cp" type="password" name="current_password" required class="input @error('current_password') input-error @enderror">@error('current_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
        <div><label for="np" class="label">New password</label><input id="np" type="password" name="password" required autocomplete="new-password" class="input @error('password') input-error @enderror">@error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
        <div><label for="np2" class="label">Confirm new password</label><input id="np2" type="password" name="password_confirmation" required class="input"></div>
        <button class="btn btn-dark">Update password</button>
    </form>
</div>
@endsection
