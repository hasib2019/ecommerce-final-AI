<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\CacheVersion;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Seeds a realistic demo store. Product data & photos come from the public
 * dummyjson.com catalogue; banners are generated locally with GD.
 * If the network is unavailable, products get generated placeholder images.
 */
class DemoSeeder extends Seeder
{
    /** dummyjson category => [parent name, child name] */
    private const CATEGORY_MAP = [
        'smartphones' => ['Electronics', 'Smartphones'],
        'laptops' => ['Electronics', 'Laptops'],
        'tablets' => ['Electronics', 'Tablets'],
        'mobile-accessories' => ['Electronics', 'Mobile Accessories'],
        'mens-shirts' => ["Men's Fashion", 'Shirts'],
        'mens-shoes' => ["Men's Fashion", "Men's Shoes"],
        'mens-watches' => ["Men's Fashion", "Men's Watches"],
        'womens-dresses' => ["Women's Fashion", 'Dresses'],
        'tops' => ["Women's Fashion", 'Tops'],
        'womens-bags' => ["Women's Fashion", 'Bags'],
        'womens-shoes' => ["Women's Fashion", "Women's Shoes"],
        'womens-jewellery' => ["Women's Fashion", 'Jewellery'],
        'womens-watches' => ["Women's Fashion", "Women's Watches"],
        'beauty' => ['Beauty & Health', 'Makeup'],
        'fragrances' => ['Beauty & Health', 'Fragrances'],
        'skin-care' => ['Beauty & Health', 'Skin Care'],
        'furniture' => ['Home & Living', 'Furniture'],
        'home-decoration' => ['Home & Living', 'Home Decor'],
        'kitchen-accessories' => ['Home & Living', 'Kitchen'],
        'sports-accessories' => ['Sports & Outdoor', 'Sports Gear'],
        'sunglasses' => ['Sports & Outdoor', 'Sunglasses'],
        'groceries' => ['Groceries', null],
    ];

    private const NAMES = ['Rahim Uddin', 'Karim Hossain', 'Nusrat Jahan', 'Tanvir Ahmed', 'Farhana Akter', 'Sabbir Rahman', 'Mitu Islam', 'Arif Chowdhury', 'Sumaiya Khan', 'Imran Hasan', 'Jannatul Ferdous', 'Rakib Hasan', 'Sadia Afrin', 'Mahmudul Hasan', 'Tania Sultana', 'Shakil Ahmed', 'Rumana Begum', 'Asif Iqbal', 'Lamia Rahman', 'Nayeem Islam'];

    private const CITIES = ['Dhaka', 'Dhaka', 'Dhaka', 'Chattogram', 'Gazipur', 'Sylhet', 'Narayanganj', 'Rajshahi', 'Khulna', 'Cumilla', 'Barishal', 'Rangpur', 'Mymensingh', 'Bogura'];

    private const REVIEW_TEXT = [
        5 => ['Excellent product! Exactly as described and delivery was super fast.', 'Original product, very happy with the quality. Highly recommended!', 'Best price in Bangladesh. Packaging was great too.', 'Got it within 24 hours in Dhaka. Will order again!', 'Amazing quality for the price. Thank you!', 'চমৎকার প্রোডাক্ট, একদম অরিজিনাল। ধন্যবাদ!'],
        4 => ['Good product, works well. Delivery took 2 days.', 'Quality is good, slightly different colour than the photo but nice.', 'Value for money. Customer service was helpful.', 'Satisfied with the purchase. Recommended.'],
        3 => ['Average, but okay for the price.', 'Product is fine, delivery was a bit late.'],
    ];

    private string $imgDir;

    public function run(): void
    {
        $this->command?->info('Seeding demo store…');
        $this->imgDir = 'demo';

        $this->settings();
        $this->users();
        $this->shipping();
        $this->coupons();
        $this->pages();
        $catalog = $this->catalog();
        $this->posts($catalog);
        $this->homepage($catalog);
        $this->orders();

        CacheVersion::bump();
        $this->command?->info('Demo store ready.');
    }

    // ------------------------------------------------------------------ settings & users

    private function settings(): void
    {
        Settings::save('general', array_merge(config('shop.defaults.general'), ['site_name' => 'ShopBD']));
    }

    private function users(): void
    {
        User::updateOrCreate(['email' => 'admin@shop.test'], [
            'name' => 'Store Admin', 'phone' => '01700000000', 'password' => 'password',
        ])->forceFill(['is_admin' => true])->save();

        User::updateOrCreate(['email' => 'customer@shop.test'], [
            'name' => 'Rahim Uddin', 'phone' => '01711111111', 'password' => 'password',
            'address' => 'House 7, Road 3, Mirpur 10', 'city' => 'Dhaka',
        ]);
    }

    private function shipping(): void
    {
        foreach ([
            ['Inside Dhaka', 'Delivery in 1–2 days', 70, 1],
            ['Dhaka Suburbs', 'Savar, Gazipur, Narayanganj, Keraniganj · 2–3 days', 100, 2],
            ['Outside Dhaka', 'All over Bangladesh · 2–4 days', 130, 3],
        ] as [$name, $desc, $cost, $sort]) {
            ShippingMethod::updateOrCreate(['name' => $name], ['description' => $desc, 'cost' => $cost, 'sort_order' => $sort, 'is_active' => true]);
        }
    }

    private function coupons(): void
    {
        Coupon::updateOrCreate(['code' => 'WELCOME10'], ['description' => '10% off your first order', 'type' => 'percent', 'value' => 10, 'max_discount' => 500, 'is_active' => true]);
        Coupon::updateOrCreate(['code' => 'SAVE200'], ['description' => '৳200 off orders over ৳3,000', 'type' => 'fixed', 'value' => 200, 'min_order' => 3000, 'is_active' => true]);
        Coupon::updateOrCreate(['code' => 'FREESHIP'], ['description' => 'Free delivery on orders over ৳1,000', 'type' => 'fixed', 'value' => 0, 'min_order' => 1000, 'free_shipping' => true, 'is_active' => true]);
    }

    private function pages(): void
    {
        $site = 'ShopBD';
        $pages = [
            'About Us' => "<p>{$site} is a Bangladeshi online store on a mission to make genuine products affordable and accessible to everyone, everywhere in the country.</p><h2>Why shop with us?</h2><ul><li>100% authentic products sourced directly from brands and authorised distributors</li><li>Cash on Delivery across all 64 districts</li><li>Fast delivery – 24 hours inside Dhaka</li><li>Easy 7-day return &amp; exchange</li><li>Friendly support team, 7 days a week</li></ul>",
            'Privacy Policy' => '<p>We respect your privacy. We only collect the information needed to process your orders (name, phone, address and optionally email) and never sell your personal data.</p><h2>Information we collect</h2><p>Order details, contact information and basic analytics to improve our service.</p><h2>How we use it</h2><p>To deliver your orders, provide support and – only if you subscribe – send offers.</p><h2>Contact</h2><p>Questions? Contact us any time from the Contact page.</p>',
            'Terms and Conditions' => '<p>By using this website you agree to the following terms.</p><h2>Orders</h2><p>All orders are subject to availability and confirmation by phone. Prices may change without prior notice.</p><h2>Payment</h2><p>We accept Cash on Delivery, bKash and Nagad.</p><h2>Delivery</h2><p>Delivery times are estimates and may vary during holidays or bad weather.</p>',
            'Return Policy' => '<p>Not happy? You can return or exchange most items within <strong>7 days</strong> of delivery.</p><ul><li>The product must be unused and in its original packaging</li><li>Damaged or wrong items are replaced free of charge</li><li>Refunds are processed within 3–5 working days via bKash or bank</li></ul>',
            'Shipping Policy' => '<table><tr><th>Area</th><th>Charge</th><th>Time</th></tr><tr><td>Inside Dhaka</td><td>৳70</td><td>1–2 days</td></tr><tr><td>Dhaka Suburbs</td><td>৳100</td><td>2–3 days</td></tr><tr><td>Outside Dhaka</td><td>৳130</td><td>2–4 days</td></tr></table><p>Free delivery on orders over ৳2,000.</p>',
        ];

        foreach ($pages as $title => $content) {
            Page::updateOrCreate(['slug' => Str::slug($title)], ['title' => $title, 'content' => $content, 'is_active' => true]);
        }
    }

    // ------------------------------------------------------------------ catalog

    /** @return array<string, mixed> */
    private function catalog(): array
    {
        $data = $this->fetchCatalog();
        $this->command?->info('  · '.count($data).' products to import');

        // Download images in parallel batches.
        $this->downloadImages($data);

        $parents = [];
        $children = [];
        $brands = [];
        $sort = 0;

        foreach (self::CATEGORY_MAP as [$parentName, $childName]) {
            $parents[$parentName] ??= Category::updateOrCreate(['slug' => Str::slug($parentName)], [
                'name' => $parentName, 'is_active' => true, 'is_featured' => true, 'sort_order' => $sort++,
                'description' => "Shop the best {$parentName} products online in Bangladesh – genuine items, great prices and fast delivery.",
            ]);
            if ($childName) {
                $children[$childName] ??= Category::updateOrCreate(['slug' => Str::slug($childName)], [
                    'name' => $childName, 'parent_id' => $parents[$parentName]->id, 'is_active' => true, 'sort_order' => $sort++,
                ]);
            }
        }

        $products = [];
        foreach ($data as $i => $p) {
            [$parentName, $childName] = self::CATEGORY_MAP[$p['category']];
            $category = $childName ? $children[$childName] : $parents[$parentName];

            $brand = null;
            if (! empty($p['brand'])) {
                $brand = $brands[$p['brand']] ??= Brand::updateOrCreate(['slug' => Str::slug($p['brand'])], [
                    'name' => $p['brand'], 'is_active' => true, 'logo' => $this->brandLogo($p['brand']),
                ]);
            }

            $price = $this->bdt($p['price']);
            $onSale = ($p['discountPercentage'] ?? 0) >= 6;
            $salePrice = $onSale ? $this->bdt($p['price'] * (1 - $p['discountPercentage'] / 100)) : null;
            if ($salePrice !== null && $salePrice >= $price) {
                $salePrice = null;
            }

            $images = $p['_local'] ?? [];
            $variants = $this->variantsFor($p['category']);

            $product = Product::updateOrCreate(['sku' => $p['sku']], [
                'category_id' => $category->id,
                'brand_id' => $brand?->id,
                'name' => $p['title'],
                'slug' => Str::slug($p['title']),
                'short_description' => $p['description'],
                'description' => $this->description($p),
                'price' => $price,
                'sale_price' => $salePrice,
                'sale_ends_at' => $salePrice && $i % 4 === 0 ? now()->addDays(2 + $i % 5)->setTime(23, 59) : null,
                'cost_price' => round($price * 0.7),
                'stock' => $variants ? 0 : max(0, (int) $p['stock']),
                'track_stock' => true,
                'low_stock_threshold' => 5,
                'thumbnail' => $images[0] ?? null,
                'gallery' => array_slice($images, 1),
                'badge' => $i % 17 === 0 ? 'Hot' : ($i % 23 === 0 ? 'Best Seller' : null),
                'weight' => $p['weight'] ?? null,
                'has_variants' => (bool) $variants,
                'options' => $variants['options'] ?? null,
                'specifications' => $this->specs($p),
                'is_active' => true,
                'is_featured' => $i % 5 === 0,
                'views' => random_int(40, 2500),
                'sold_count' => random_int(0, 420),
                'meta_keywords' => implode(', ', (array) ($p['tags'] ?? [])),
            ]);

            if ($variants) {
                $product->variants()->delete();
                foreach ($variants['combos'] as $k => $combo) {
                    $product->variants()->create([
                        'name' => implode(' / ', $combo),
                        'options' => $combo,
                        'sku' => $p['sku'].'-'.Str::upper(Str::slug(implode('-', $combo))),
                        'price' => null,
                        'stock' => $k === 1 ? 0 : random_int(3, 25),
                        'is_active' => true,
                        'sort_order' => $k,
                    ]);
                }
                $product->syncStockFromVariants();
            }

            // Spread creation dates so only the newest products get the "New" badge.
            $product->forceFill(['created_at' => now()->subDays($i < 15 ? random_int(0, 10) : random_int(25, 120))])->saveQuietly();

            $this->reviews($product, $i);
            $products[] = $product;
        }

        // Category images = first product photo in that category.
        foreach ([...$children, ...$parents] as $cat) {
            if (! $cat->image) {
                $first = Product::whereIn('category_id', $cat->descendantIds())->whereNotNull('thumbnail')->orderBy('id')->first();
                $cat->update(['image' => $first?->thumbnail]);
            }
        }

        return ['parents' => $parents, 'children' => $children, 'products' => $products];
    }

    private function fetchCatalog(): array
    {
        try {
            $json = Http::timeout(30)->retry(2, 500)->get('https://dummyjson.com/products', ['limit' => 0])->throw()->json('products');

            return array_values(array_filter($json, fn ($p) => isset(self::CATEGORY_MAP[$p['category']])));
        } catch (Throwable $e) {
            $this->command?->warn('  · Could not reach dummyjson.com – using an offline sample catalogue.');

            return $this->offlineCatalog();
        }
    }

    private function downloadImages(array &$data): void
    {
        $disk = Storage::disk('public');
        $jobs = [];

        foreach ($data as $idx => $p) {
            $slug = Str::slug($p['title']);
            $urls = array_slice($p['images'] ?? [], 0, 4);
            $data[$idx]['_local'] = [];
            foreach ($urls as $n => $url) {
                $path = "products/{$slug}/".($n + 1).'.webp';
                $data[$idx]['_local'][] = $path;
                if (! $disk->exists($path)) {
                    $jobs[] = [$url, $path];
                }
            }
            if (! $urls) {
                $path = "products/{$slug}/1.png";
                $this->placeholder($path, $p['title']);
                $data[$idx]['_local'][] = $path;
            }
        }

        $this->command?->info('  · Downloading '.count($jobs).' product photos…');
        foreach (array_chunk($jobs, 24) as $chunk) {
            try {
                $responses = Http::pool(fn ($pool) => array_map(fn ($j) => $pool->timeout(30)->get($j[0]), $chunk));
            } catch (Throwable) {
                $responses = [];
            }
            foreach ($chunk as $k => [$url, $path]) {
                $res = $responses[$k] ?? null;
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful() && strlen($res->body()) > 200) {
                    $disk->put($path, $res->body());
                }
            }
        }

        // Drop images that failed to download; generate a placeholder if none remain.
        foreach ($data as $idx => $p) {
            $data[$idx]['_local'] = array_values(array_filter($data[$idx]['_local'], fn ($path) => $disk->exists($path)));
            if (! $data[$idx]['_local']) {
                $path = 'products/'.Str::slug($p['title']).'/placeholder.png';
                $this->placeholder($path, $p['title']);
                $data[$idx]['_local'] = [$path];
            }
        }
    }

    private function bdt(float $usd): int
    {
        $v = $usd * 118;

        return (int) ($v < 1000 ? round($v / 10) * 10 : round($v / 50) * 50);
    }

    private function variantsFor(string $category): ?array
    {
        $sizes = match ($category) {
            'mens-shirts', 'tops', 'womens-dresses' => ['S', 'M', 'L', 'XL'],
            'mens-shoes', 'womens-shoes' => ['39', '40', '41', '42', '43'],
            default => null,
        };
        if (! $sizes) {
            return null;
        }

        $options = [['name' => 'Size', 'values' => $sizes]];
        $combos = array_map(fn ($s) => ['Size' => $s], $sizes);

        if (in_array($category, ['mens-shirts', 'tops'], true)) {
            $colors = ['Black', 'White', 'Navy'];
            $options[] = ['name' => 'Color', 'values' => $colors];
            $combos = [];
            foreach ($sizes as $s) {
                foreach ($colors as $c) {
                    $combos[] = ['Size' => $s, 'Color' => $c];
                }
            }
        }

        return compact('options', 'combos');
    }

    private function description(array $p): string
    {
        $tags = implode(', ', array_map('ucfirst', (array) ($p['tags'] ?? [])));

        return '<p>'.e($p['description']).'</p>'
            .'<h3>Key features</h3><ul>'
            .'<li>100% original '.e($p['brand'] ?? 'brand').' product</li>'
            .'<li>'.e($p['warrantyInformation'] ?? 'Official warranty').'</li>'
            .'<li>Cash on Delivery available all over Bangladesh</li>'
            .'<li>Easy 7-day return &amp; exchange</li>'
            .'</ul>'
            .($tags ? '<p><strong>Tags:</strong> '.e($tags).'</p>' : '');
    }

    private function specs(array $p): array
    {
        $dims = $p['dimensions'] ?? [];

        return array_values(array_filter([
            ! empty($p['brand']) ? ['label' => 'Brand', 'value' => $p['brand']] : null,
            ['label' => 'Model / SKU', 'value' => $p['sku']],
            ! empty($p['weight']) ? ['label' => 'Weight', 'value' => $p['weight'].' kg'] : null,
            $dims ? ['label' => 'Dimensions', 'value' => sprintf('%s × %s × %s cm', $dims['width'] ?? '-', $dims['height'] ?? '-', $dims['depth'] ?? '-')] : null,
            ['label' => 'Warranty', 'value' => $p['warrantyInformation'] ?? 'N/A'],
            ['label' => 'Country of origin', 'value' => Arr::random(['China', 'Japan', 'USA', 'Korea', 'Bangladesh', 'Thailand'])],
        ]));
    }

    private function reviews(Product $product, int $i): void
    {
        if ($product->reviews()->exists()) {
            return;
        }

        $count = [0, 2, 3, 4, 5, 6][$i % 6];
        for ($r = 0; $r < $count; $r++) {
            $rating = Arr::random([5, 5, 5, 4, 4, 3]);
            ProductReview::create([
                'product_id' => $product->id,
                'name' => Arr::random(self::NAMES),
                'rating' => $rating,
                'comment' => Arr::random(self::REVIEW_TEXT[$rating]),
                'is_verified' => (bool) random_int(0, 1),
                'is_approved' => true,
                'created_at' => now()->subDays(random_int(1, 120)),
            ]);
        }
    }

    // ------------------------------------------------------------------ content

    private function posts(array $catalog): void
    {
        $user = User::where('is_admin', true)->first();
        $samples = [
            ['How to Spot a Genuine Smartphone in Bangladesh', 'smartphones', [99, 102, 241], [139, 92, 246],
                'Buying a phone online? Here is a quick checklist to make sure you get an original device with official warranty.'],
            ['10 Kitchen Gadgets That Save You Time Every Day', 'kitchen-accessories', [16, 185, 129], [5, 150, 105],
                'From smart choppers to non-stick pans – the affordable tools that make cooking faster and more fun.'],
            ['Skincare Routine for Humid Weather: A Simple Guide', 'skin-care', [244, 63, 94], [251, 146, 60],
                'Bangladesh summers are hot and humid. Here is a gentle 4-step routine that actually works.'],
        ];

        foreach ($samples as $k => [$title, $cat, $from, $to, $excerpt]) {
            $thumb = Product::whereHas('category', fn ($q) => $q->where('slug', Str::slug(self::CATEGORY_MAP[$cat][1] ?? $cat)))
                ->whereNotNull('thumbnail')->value('thumbnail');
            $image = $this->banner("blog/post-{$k}.jpg", 1280, 720, $from, $to, $thumb);

            Post::updateOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $user?->id,
                'title' => $title,
                'excerpt' => $excerpt,
                'image' => $image,
                'content' => '<p>'.$excerpt.'</p><h2>1. Start with the basics</h2><p>Always buy from a trusted shop that offers cash on delivery and a clear return policy. Check reviews from verified buyers before you order.</p><h2>2. Compare prices – but carefully</h2><p>If a price looks too good to be true, it probably is. Compare with official brand prices and look for warranty information.</p><h2>3. Check the product on delivery</h2><p>With Cash on Delivery you can inspect the package before paying. Keep the invoice for warranty claims.</p><blockquote>Tip: Subscribe to our newsletter to get early access to flash sales.</blockquote>',
                'is_published' => true,
                'published_at' => now()->subDays(3 + $k * 6),
            ]);
        }
    }

    private function homepage(array $catalog): void
    {
        HomeSection::query()->delete();
        $p = $catalog['parents'];
        $pick = fn (string $childSlug) => Product::whereHas('category', fn ($q) => $q->where('slug', $childSlug))->whereNotNull('thumbnail')->inRandomOrder()->value('thumbnail');

        $slides = [
            ['Mega Electronics Sale', 'Up to 30% off smartphones, laptops & accessories. Official warranty.', 'Shop Electronics', '/category/electronics', [67, 56, 202], [147, 51, 234], $pick('smartphones'), 'Limited time'],
            ['New Season Fashion', 'Fresh styles for men & women. Free delivery on orders over ৳2,000.', 'Explore Fashion', '/category/womens-fashion', [225, 29, 72], [249, 115, 22], $pick('womens-bags'), 'New arrivals'],
            ['Glow Up With Beauty Picks', 'Genuine skincare, makeup & fragrances delivered to your door.', 'Shop Beauty', '/category/beauty-health', [13, 148, 136], [16, 185, 129], $pick('fragrances'), '100% genuine'],
        ];

        $heroSlides = [];
        foreach ($slides as $k => [$heading, $sub, $btn, $link, $from, $to, $img, $badge]) {
            $heroSlides[] = [
                'image' => $this->banner("banners/hero-{$k}.jpg", 1600, 700, $from, $to, $img, 'right'),
                'mobile_image' => $this->banner("banners/hero-{$k}-m.jpg", 900, 1000, $from, $to, $img, 'bottom'),
                'heading' => $heading, 'subheading' => $sub, 'button_text' => $btn, 'button_link' => $link,
                'badge' => $badge, 'align' => 'left', 'text_color' => 'light', 'overlay' => false,
            ];
        }

        $side = [
            ['image' => $this->banner('banners/side-0.jpg', 800, 500, [15, 23, 42], [51, 65, 85], $pick('mens-watches'), 'right'), 'heading' => 'Watches up to 25% off', 'subheading' => 'Men & Women', 'button_text' => 'Shop now', 'link' => '/category/mens-watches', 'text_color' => 'light'],
            ['image' => $this->banner('banners/side-1.jpg', 800, 500, [254, 243, 199], [253, 186, 116], $pick('kitchen'), 'right'), 'heading' => 'Kitchen essentials', 'subheading' => 'From ৳199', 'button_text' => 'Shop now', 'link' => '/category/kitchen', 'text_color' => 'dark'],
        ];

        $sections = [
            ['hero_slider', null, null, ['slides' => $heroSlides, 'side_banners' => $side, 'autoplay' => true, 'interval' => 5, 'height' => 'md']],
            ['features', null, null, ['items' => [
                ['icon' => 'truck', 'title' => 'Fast Delivery', 'text' => '24h inside Dhaka'],
                ['icon' => 'cash', 'title' => 'Cash on Delivery', 'text' => 'Pay when you receive'],
                ['icon' => 'refresh', 'title' => 'Easy Returns', 'text' => '7-day return policy'],
                ['icon' => 'shield', 'title' => '100% Genuine', 'text' => 'Authentic products only'],
            ]]],
            ['category_grid', 'Shop by Category', 'Find exactly what you need', ['style' => 'circle', 'category_ids' => [], 'limit' => 16]],
            ['flash_sale', 'Flash Sale', 'Grab these deals before they are gone!', ['source' => 'on_sale', 'limit' => 12, 'ends_at' => now()->addDays(3)->setTime(23, 59)->toDateTimeString(), 'view_all_url' => '/shop?on_sale=1']],
            ['products', 'New Arrivals', 'The latest products, just landed', ['source' => 'latest', 'limit' => 12, 'layout' => 'slider', 'columns' => 5, 'view_all_url' => '/shop?sort=latest']],
            ['banners', null, null, ['columns' => 2, 'banners' => [
                ['image' => $this->banner('banners/men.jpg', 1200, 600, [30, 41, 59], [71, 85, 105], $pick('shirts'), 'right'), 'link' => '/category/mens-fashion', 'heading' => "Men's Collection", 'subheading' => 'Trending now', 'button_text' => 'Shop men', 'text_color' => 'light'],
                ['image' => $this->banner('banners/women.jpg', 1200, 600, [252, 231, 243], [249, 168, 212], $pick('dresses'), 'right'), 'link' => '/category/womens-fashion', 'heading' => "Women's Collection", 'subheading' => 'Up to 40% off', 'button_text' => 'Shop women', 'text_color' => 'dark'],
            ]]],
            ['products', 'Best Sellers', 'Most loved by our customers', ['source' => 'best_selling', 'limit' => 10, 'layout' => 'grid', 'columns' => 5, 'view_all_url' => '/shop?sort=popular']],
            ['products', 'Smartphones & Gadgets', null, ['source' => 'category', 'category_id' => $p['Electronics']->id, 'limit' => 12, 'layout' => 'slider', 'columns' => 5, 'view_all_url' => '/category/electronics']],
            ['promo_banner', 'Weekend Mega Deal', 'This weekend only', ['heading' => 'Extra 10% off with code WELCOME10', 'text' => 'Stack it with sale prices. Valid on all categories – hurry, the offer ends soon!', 'button_text' => 'Shop the deal', 'button_link' => '/shop?on_sale=1', 'ends_at' => now()->addDays(2)->setTime(23, 59)->toDateTimeString(), 'align' => 'center', 'image' => $this->banner('banners/promo.jpg', 1600, 500, [15, 23, 42], [88, 28, 135], $pick('laptops'), 'right')]],
            ['products', 'Home & Kitchen Picks', null, ['source' => 'category', 'category_id' => $p['Home & Living']->id, 'limit' => 12, 'layout' => 'slider', 'columns' => 5, 'view_all_url' => '/category/home-living']],
            ['products', 'Top Rated', 'Highest rated by verified buyers', ['source' => 'top_rated', 'limit' => 10, 'layout' => 'grid', 'columns' => 5]],
            ['testimonials', 'What Our Customers Say', null, ['items' => [
                ['name' => 'Nusrat Jahan', 'location' => 'Dhanmondi, Dhaka', 'rating' => 5, 'text' => 'Ordered at night and got it the next afternoon. Product was 100% original. Very impressed!'],
                ['name' => 'Tanvir Ahmed', 'location' => 'Chattogram', 'rating' => 5, 'text' => 'Cash on delivery made it easy to trust them. Packaging was excellent and the price was the best I found.'],
                ['name' => 'Farhana Akter', 'location' => 'Sylhet', 'rating' => 4, 'text' => 'Customer service called to confirm my order quickly. Exchange for a different size was hassle-free.'],
            ]]],
            ['brands', 'Top Brands', null, ['limit' => 12, 'grayscale' => true]],
            ['blog_posts', 'From the Blog', 'Tips, guides & buying advice', ['limit' => 3]],
            ['newsletter', 'Get ৳100 off your next order', 'Subscribe for exclusive deals, new arrivals and flash-sale alerts.', ['button_text' => 'Subscribe']],
        ];

        foreach ($sections as $sort => [$type, $title, $subtitle, $data]) {
            HomeSection::create(['type' => $type, 'title' => $title, 'subtitle' => $subtitle, 'data' => $data, 'sort_order' => $sort, 'is_active' => true]);
        }
    }

    private function orders(): void
    {
        if (Order::count() >= 20) {
            return;
        }

        $products = Product::where('has_variants', false)->where('stock', '>', 5)->inRandomOrder()->limit(40)->get();
        $customer = User::where('email', 'customer@shop.test')->first();
        $methods = ShippingMethod::orderBy('sort_order')->get();
        $statuses = [OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Shipped, OrderStatus::Processing, OrderStatus::Confirmed, OrderStatus::Pending, OrderStatus::Pending, OrderStatus::Cancelled];

        for ($n = 0; $n < 36; $n++) {
            $items = $products->random(random_int(1, 3));
            $city = Arr::random(self::CITIES);
            $method = $city === 'Dhaka' ? $methods[0] : $methods[2];
            $subtotal = 0;
            $lines = [];
            foreach ($items as $prod) {
                $qty = random_int(1, 2);
                $lines[] = [$prod, $qty];
                $subtotal += $prod->final_price * $qty;
            }
            $shipping = $subtotal >= 2000 ? 0 : (float) $method->cost;
            $status = $n < 3 ? OrderStatus::Pending : Arr::random($statuses);
            $created = now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1400));
            if ($n < 6) {
                $created = now()->subHours($n * 3 + 1);
            }
            $name = Arr::random(self::NAMES);

            $order = Order::create([
                'user_id' => $n % 6 === 0 ? $customer?->id : null,
                'customer_name' => $n % 6 === 0 ? $customer?->name : $name,
                'customer_phone' => '017'.random_int(10000000, 99999999),
                'shipping_address' => 'House '.random_int(1, 99).', Road '.random_int(1, 20).', '.Arr::random(['Mirpur', 'Uttara', 'Banani', 'Mohammadpur', 'Bashundhara', 'Agrabad', 'Zindabazar']),
                'city' => $city,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'total' => $subtotal + $shipping,
                'shipping_method' => $method->name,
                'payment_method' => Arr::random(['cod', 'cod', 'cod', 'bkash']),
                'payment_status' => $status === OrderStatus::Delivered ? PaymentStatus::Paid : PaymentStatus::Unpaid,
                'status' => OrderStatus::Pending,
            ]);

            foreach ($lines as [$prod, $qty]) {
                $order->items()->create([
                    'product_id' => $prod->id, 'product_name' => $prod->name, 'sku' => $prod->sku, 'image' => $prod->thumbnail,
                    'price' => $prod->final_price, 'quantity' => $qty, 'total' => $prod->final_price * $qty,
                ]);
            }

            if ($status !== OrderStatus::Pending) {
                $order->update(['status' => $status]);
            }
            $order->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();
            $order->histories()->update(['created_at' => $created]);
        }
    }

    // ------------------------------------------------------------------ image generation (GD)

    /** Gradient banner with a product photo composited on it. Returns the public-disk path. */
    private function banner(string $path, int $w, int $h, array $from, array $to, ?string $productImage = null, string $layout = 'right'): string
    {
        $disk = Storage::disk('public');
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);

        // Diagonal gradient
        for ($x = 0; $x < $w; $x++) {
            $t = $x / max(1, $w - 1);
            $c = imagecolorallocate($im, (int) ($from[0] + ($to[0] - $from[0]) * $t), (int) ($from[1] + ($to[1] - $from[1]) * $t), (int) ($from[2] + ($to[2] - $from[2]) * $t));
            imageline($im, $x, 0, $x, $h, $c);
        }

        // Soft decorative circles
        $white = imagecolorallocatealpha($im, 255, 255, 255, 112);
        $white2 = imagecolorallocatealpha($im, 255, 255, 255, 120);
        imagefilledellipse($im, (int) ($w * 0.78), (int) ($h * 0.5), (int) ($h * 1.05), (int) ($h * 1.05), $white);
        imagefilledellipse($im, (int) ($w * 0.08), (int) ($h * 0.95), (int) ($h * 0.6), (int) ($h * 0.6), $white2);
        imagefilledellipse($im, (int) ($w * 0.55), (int) ($h * 0.08), (int) ($h * 0.3), (int) ($h * 0.3), $white2);

        if ($productImage && $disk->exists($productImage)) {
            $src = @imagecreatefromstring($disk->get($productImage));
            if ($src) {
                imagealphablending($src, true);
                $size = $layout === 'bottom' ? (int) ($w * 0.8) : (int) ($h * 0.9);
                $dx = $layout === 'bottom' ? (int) (($w - $size) / 2) : (int) ($w * 0.78 - $size / 2);
                $dy = $layout === 'bottom' ? (int) ($h - $size * 0.95) : (int) (($h - $size) / 2);
                imagecopyresampled($im, $src, $dx, $dy, 0, 0, $size, $size, imagesx($src), imagesy($src));
                imagedestroy($src);
            }
        }

        // Darken left side slightly so overlay text stays readable on light gradients.
        $disk->makeDirectory(dirname($path));
        imagejpeg($im, $disk->path($path), 86);
        imagedestroy($im);

        return $path;
    }

    private function placeholder(string $path, string $text): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory(dirname($path));
        $im = imagecreatetruecolor(800, 800);
        $hue = crc32($text) % 360;
        [$r, $g, $b] = $this->hsl($hue, 0.55, 0.88);
        imagefill($im, 0, 0, imagecolorallocate($im, $r, $g, $b));
        [$r, $g, $b] = $this->hsl($hue, 0.5, 0.45);
        $ink = imagecolorallocate($im, $r, $g, $b);
        imagefilledellipse($im, 400, 340, 300, 300, $ink);
        $font = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
        if (function_exists('imagettftext') && is_file($font)) {
            $label = Str::limit($text, 26);
            $box = imagettfbbox(30, 0, $font, $label);
            imagettftext($im, 30, 0, (int) (400 - ($box[2] - $box[0]) / 2), 620, $ink, $font, $label);
        }
        imagepng($im, $disk->path($path));
        imagedestroy($im);
    }

    private function brandLogo(string $name): string
    {
        $path = 'brands/'.Str::slug($name).'.svg';
        $size = mb_strlen($name) > 14 ? 20 : 26;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 64"><text x="120" y="42" text-anchor="middle" font-family="Georgia, \'Times New Roman\', serif" font-weight="700" font-size="'.$size.'" letter-spacing="1" fill="#0f172a">'.e($name).'</text></svg>';
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    private function hsl(int $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;
        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0], $h < 120 => [$x, $c, 0], $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c], $h < 300 => [$x, 0, $c], default => [$c, 0, $x],
        };

        return [(int) (($r + $m) * 255), (int) (($g + $m) * 255), (int) (($b + $m) * 255)];
    }

    private function offlineCatalog(): array
    {
        $items = [
            ['smartphones', 'Galaxy Nova 5G Smartphone', 'Samsung', 349], ['smartphones', 'Pixel Lite 128GB', 'Google', 299],
            ['laptops', 'UltraBook Pro 14"', 'Asus', 899], ['mobile-accessories', 'Wireless Earbuds X2', 'Xiaomi', 39],
            ['mens-shirts', 'Classic Oxford Shirt', 'Yellow', 25], ['mens-shoes', 'Running Sneakers Air', 'Nike', 79],
            ['mens-watches', 'Chronograph Steel Watch', 'Casio', 129], ['womens-dresses', 'Floral Summer Dress', 'Aarong', 35],
            ['womens-bags', 'Leather Tote Bag', 'Apex', 59], ['beauty', 'Matte Lipstick Set', 'Maybelline', 19],
            ['fragrances', 'Ocean Breeze Perfume', 'Dior', 89], ['skin-care', 'Vitamin C Serum', 'Cerave', 24],
            ['furniture', 'Ergonomic Office Chair', 'Hatil', 199], ['kitchen-accessories', 'Non-stick Fry Pan 26cm', 'Kiam', 22],
            ['home-decoration', 'Ceramic Table Lamp', 'Ikea', 45], ['sports-accessories', 'Football Size 5', 'Adidas', 18],
            ['sunglasses', 'Polarized Aviator', 'Ray-Ban', 69], ['groceries', 'Premium Basmati Rice 5kg', 'Pran', 12],
        ];

        return array_map(fn ($it, $k) => [
            'category' => $it[0], 'title' => $it[1], 'brand' => $it[2], 'price' => $it[3], 'sku' => 'DEMO-'.str_pad((string) ($k + 1), 3, '0', STR_PAD_LEFT),
            'description' => "The {$it[1]} by {$it[2]} combines quality and value. A customer favourite.", 'discountPercentage' => $k % 2 ? 12 : 0,
            'stock' => 20 + $k, 'images' => [], 'tags' => [$it[0]], 'warrantyInformation' => '1 year warranty', 'weight' => 1,
        ], $items, array_keys($items));
    }
}
