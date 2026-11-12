<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __invoke(Request $request, Order $order)
    {
        abort_unless($request->user()?->is_admin, 403);
        $order->load('items');

        return view('invoice', compact('order'));
    }
}
