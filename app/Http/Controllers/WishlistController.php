<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private Wishlist $wishlist) {}

    public function index()
    {
        seo()->title('My Wishlist')->noindex();

        $ids = $this->wishlist->ids();
        $products = $ids ? Product::forCard()->whereIn('id', $ids)->get() : collect();

        return view('wishlist', compact('products'));
    }

    public function toggle(Request $request)
    {
        $id = (int) $request->input('product_id');
        abort_unless(Product::whereKey($id)->exists(), 404);

        $added = $this->wishlist->toggle($id);

        return response()->json([
            'ok' => true,
            'added' => $added,
            'count' => $this->wishlist->count(),
            'message' => $added ? 'Saved to wishlist' : 'Removed from wishlist',
        ]);
    }
}
