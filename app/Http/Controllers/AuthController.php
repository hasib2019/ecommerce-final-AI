<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        seo()->title('Sign in')->noindex();

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        if ($field === 'phone') {
            $login = preg_replace('/^\+?88/', '', preg_replace('/[\s\-]/', '', $login));
        }

        if (! Auth::attempt([$field => $login, 'password' => $data['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['login' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.index'))->with('success', 'Welcome back, '.Auth::user()->name.'!');
    }

    public function showRegister()
    {
        seo()->title('Create an account')->noindex();

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => preg_replace('/^\+?88/', '', preg_replace('/[\s\-]/', '', (string) $request->input('phone')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:'.CheckoutController::PHONE_REGEX, 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ], [
            'phone.regex' => 'Please enter a valid Bangladeshi mobile number.',
            'phone.unique' => 'An account with this phone number already exists.',
        ]);

        $user = User::create($data);
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'))->with('success', 'Your account has been created!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgot()
    {
        seo()->title('Forgot password')->noindex();

        return view('auth.forgot');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Same response whether or not the email exists (no account enumeration).
        return back()->with('success', 'If an account exists for that email, we have sent a password reset link.');
    }

    public function showReset(Request $request, string $token)
    {
        seo()->title('Reset password')->noindex();

        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Your password has been reset. Please sign in.')
            : back()->withErrors(['email' => __($status)]);
    }
}
