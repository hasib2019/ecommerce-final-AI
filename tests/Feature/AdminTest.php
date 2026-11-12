<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin());

        $urls = [
            '/admin',
            '/admin/products', '/admin/products/create', '/admin/products/'.Product::where('has_variants', true)->value('slug').'/edit', '/admin/products/'.Product::where('has_variants', false)->value('slug').'/edit',
            '/admin/categories', '/admin/categories/'.Category::value('slug').'/edit',
            '/admin/brands', '/admin/product-reviews',
            '/admin/orders', '/admin/orders/create', '/admin/orders/'.Order::value('order_number'), '/admin/orders/'.Order::value('order_number').'/edit',
            '/admin/incomplete-orders', '/admin/coupons', '/admin/coupons/create', '/admin/shipping-methods', '/admin/users',
            '/admin/home-sections', '/admin/home-sections/create',
            '/admin/pages', '/admin/posts', '/admin/subscribers', '/admin/contact-messages', '/admin/redirects',
            '/admin/store-settings', '/admin/theme-settings', '/admin/seo-settings', '/admin/sales-boosters',
        ];

        foreach (HomeSection::pluck('id') as $id) {
            $urls[] = '/admin/home-sections/'.$id.'/edit';
        }

        foreach ($urls as $url) {
            $response = $this->get($url);
            $this->assertSame(200, $response->status(), "Failed: {$url}");
        }

        $this->get(route('admin.invoice', Order::first()))->assertOk()->assertSee('INVOICE');
    }

    public function test_customers_cannot_access_admin(): void
    {
        $this->actingAs(User::where('is_admin', false)->firstOrFail());

        $this->get('/admin')->assertForbidden();
        $this->get(route('admin.invoice', Order::first()))->assertForbidden();
    }
}
