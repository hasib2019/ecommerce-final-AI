<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_public_pages_render(): void
    {
        $product = Product::where('is_active', true)->first();
        $category = Category::where('is_active', true)->first();

        foreach (['/', '/shop', '/shop?on_sale=1&sort=price_asc', '/search?q=phone', '/brands', '/blog', '/contact', '/track-order',
            '/cart', '/wishlist', '/login', '/register', '/about-us', $product->url, $category->url] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_product_page_has_seo_markup(): void
    {
        $product = Product::where('is_active', true)->where('rating_count', '>', 0)->first();

        $this->get($product->url)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$product->url.'">', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"aggregateRating"', false)
            ->assertSee('og:image', false);
    }

    public function test_filtered_listing_is_noindex(): void
    {
        $this->get('/shop?sort=price_desc')->assertSee('noindex, follow', false);
        $this->get('/shop')->assertSee('index, follow', false);
    }

    public function test_seo_endpoints(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee('sitemap-products.xml', false);
        $this->get('/sitemap-products.xml')->assertOk()->assertSee('<image:image>', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
        $this->get('/feed/products.xml')->assertOk()->assertSee('<g:price>', false);
    }

    public function test_live_search_returns_json(): void
    {
        $name = Product::where('is_active', true)->value('name');

        $this->getJson('/search/suggest?q='.urlencode(substr($name, 0, 5)))
            ->assertOk()
            ->assertJsonStructure(['products' => [['name', 'url', 'image', 'price']], 'categories']);
    }

    public function test_full_checkout_flow_creates_order_and_reduces_stock(): void
    {
        $product = Product::where('is_active', true)->where('has_variants', false)->where('stock', '>', 5)->first();
        $stockBefore = $product->stock;

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()->assertJsonPath('cart.count', 2);

        $this->postJson('/cart/coupon', ['code' => 'WELCOME10'])->assertOk();

        $this->get('/checkout')->assertOk()->assertSee('Confirm Order');

        $response = $this->post('/checkout', [
            'name' => 'Test Customer',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Dhanmondi',
            'city' => 'Dhaka',
            'shipping_method_id' => ShippingMethod::first()->id,
            'payment_method' => 'cod',
        ]);

        $order = Order::latest('id')->first();
        $response->assertRedirect(route('checkout.success', $order));

        $this->assertSame('Test Customer', $order->customer_name);
        $this->assertSame('WELCOME10', $order->coupon_code);
        $this->assertGreaterThan(0, (float) $order->discount);
        $this->assertSame($stockBefore - 2, $product->fresh()->stock);
        $this->assertSame(1, Coupon::where('code', 'WELCOME10')->value('used_count'));

        $this->get(route('checkout.success', $order))->assertOk()->assertSee($order->order_number);
        $this->get('/track-order?order='.$order->order_number.'&phone=01712345678')->assertOk()->assertSee('Pending');

        // Cancelling returns stock.
        $order->update(['status' => OrderStatus::Cancelled]);
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    public function test_checkout_rejects_invalid_phone_and_overselling(): void
    {
        $product = Product::where('is_active', true)->where('has_variants', false)->where('stock', '>', 0)->first();
        $product->update(['stock' => 1]);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 5])->assertOk()->assertJsonPath('cart.count', 1);

        $this->post('/checkout', [
            'name' => 'X', 'phone' => '12345', 'address' => 'Somewhere far away',
            'shipping_method_id' => ShippingMethod::first()->id, 'payment_method' => 'cod',
        ])->assertSessionHasErrors('phone');
    }

    public function test_variant_product_requires_option(): void
    {
        $product = Product::where('has_variants', true)->with('variants')->first();

        $this->postJson('/cart/add', ['product_id' => $product->id])->assertStatus(422);

        $variant = $product->variants->firstWhere('stock', '>', 0);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'variant_id' => $variant->id])
            ->assertOk()->assertJsonPath('cart.items.0.variant', $variant->name);
    }

    public function test_customer_can_register_and_see_account(): void
    {
        $this->post('/register', [
            'name' => 'New Buyer', 'phone' => '01899999999', 'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect(route('account.index'));

        $this->get('/account')->assertOk()->assertSee('Hello, New');
        $this->get('/admin')->assertForbidden();
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }
}
