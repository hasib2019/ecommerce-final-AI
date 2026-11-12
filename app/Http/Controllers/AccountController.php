<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        seo()->title('My Account')->noindex();

        return view('account.index', [
            'user' => $user,
            'recentOrders' => $user->orders()->withCount('items')->latest()->limit(5)->get(),
            'stats' => [
                'orders' => $user->orders()->count(),
                'pending' => $user->orders()->whereIn('status', [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped])->count(),
                'spent' => (float) $user->orders()->where('status', OrderStatus::Delivered)->sum('total'),
                'wishlist' => wishlist()->count(),
            ],
        ]);
    }

    public function orders(Request $request)
    {
        seo()->title('My Orders')->noindex();

        return view('account.orders', [
            'orders' => $request->user()->orders()->withCount('items')->latest()->paginate(10),
        ]);
    }

    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load(['items', 'histories']);
        seo()->title('Order '.$order->order_number)->noindex();

        return view('account.order', compact('order'));
    }

    public function profile(Request $request)
    {
        seo()->title('My Profile')->noindex();

        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge(['phone' => preg_replace('/^\+?88/', '', preg_replace('/[\s\-]/', '', (string) $request->input('phone')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:'.CheckoutController::PHONE_REGEX, Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', 'Password changed.');
    }
}
