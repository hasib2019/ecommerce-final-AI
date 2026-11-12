<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\Settings\SalesBoosters;
use App\Filament\Pages\Settings\SeoSettings;
use App\Filament\Pages\Settings\StoreSettings;
use App\Filament\Pages\Settings\ThemeSettings;
use App\Filament\Resources\HomeSections\Pages\CreateHomeSection;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
    }

    public function test_theme_settings_change_the_storefront(): void
    {
        Livewire::test(ThemeSettings::class)
            ->fillForm([
                'appearance.primary_color' => '#2563eb',
                'header.layout' => 'minimal',
                'header.topbar_text' => 'Eid sale is live!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#2563eb', Settings::get('appearance.primary_color'));

        $this->get('/')->assertOk()->assertSee('--brand:#2563eb', false)->assertSee('Eid sale is live!');
    }

    public function test_other_settings_pages_save(): void
    {
        foreach ([StoreSettings::class, SeoSettings::class, SalesBoosters::class] as $page) {
            Livewire::test($page)->call('save')->assertHasNoFormErrors();
        }

        Livewire::test(StoreSettings::class)->fillForm(['general.site_name' => 'Dokan BD'])->call('save');
        $this->get('/')->assertSee('Dokan BD');
    }

    public function test_product_can_be_edited_and_created(): void
    {
        $product = Product::where('has_variants', true)->first();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['name' => 'Updated Shirt Name', 'sale_price' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Shirt Name', $product->fresh()->name);

        Storage::fake('public');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Brand New Gadget',
                'price' => 1500,
                'sale_price' => 1200,
                'stock' => 10,
                'category_id' => Category::value('id'),
                'thumbnail' => UploadedFile::fake()->image('gadget.jpg', 800, 800),
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $new = Product::where('name', 'Brand New Gadget')->firstOrFail();
        $this->assertSame('brand-new-gadget', $new->slug);
        $this->get($new->url)->assertOk()->assertSee('Brand New Gadget');
    }

    public function test_variant_generator_builds_all_combinations(): void
    {
        $result = ProductResource::generateVariants([
            ['name' => 'Size', 'values' => ['S', 'M']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ], [], 'TS');

        $this->assertCount(4, $result);
        $this->assertSame(['S / Red', 'S / Blue', 'M / Red', 'M / Blue'], array_values(array_column($result, 'name')));
    }

    public function test_home_section_can_be_added(): void
    {
        Livewire::test(CreateHomeSection::class)
            ->fillForm([
                'type' => 'products',
                'title' => 'Staff Picks',
                'data.source' => 'featured',
                'data.limit' => 4,
                'is_active' => true,
                'visibility' => 'all',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('home_sections', ['title' => 'Staff Picks']);
        $this->get('/')->assertSee('Staff Picks');
    }

    public function test_order_status_action_and_manual_order(): void
    {
        $order = Order::where('status', OrderStatus::Pending)->firstOrFail();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('updateStatus', ['status' => OrderStatus::Confirmed->value, 'note' => 'Called customer'])
            ->assertHasNoActionErrors();

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertSame('Called customer', $order->histories()->latest('id')->value('note'));

        $product = Product::where('has_variants', false)->where('stock', '>', 3)->first();
        $stock = $product->stock;

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'customer_name' => 'Phone Customer',
                'customer_phone' => '01811111111',
                'shipping_address' => 'Road 1, Uttara',
                'city' => 'Dhaka',
                'status' => OrderStatus::Confirmed->value,
                'payment_status' => 'unpaid',
                'payment_method' => 'cod',
                'shipping_method' => ShippingMethod::value('name'),
                'shipping_cost' => 70,
                'discount' => 0,
                'subtotal' => 0,
                'total' => 0,
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => $product->final_price, 'product_name' => $product->name]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $manual = Order::where('customer_name', 'Phone Customer')->firstOrFail();
        $this->assertEquals(round($product->final_price * 2 + 70, 2), (float) $manual->total);
        $this->assertSame($stock - 2, $product->fresh()->stock);
    }
}
