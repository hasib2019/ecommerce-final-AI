<?php

/*
|--------------------------------------------------------------------------
| Storefront defaults
|--------------------------------------------------------------------------
| Every value here can be overridden from the admin panel (Settings pages).
| The storefront always reads through setting('group.key') which merges the
| database values on top of these defaults, so the shop never breaks when a
| setting has not been saved yet.
*/

return [

    'defaults' => [

        'general' => [
            'site_name' => env('APP_NAME', 'ShopBD'),
            'tagline' => 'Genuine products. Fast delivery. Cash on delivery.',
            'logo' => null,
            'logo_height' => 36,
            'favicon' => null,
            'phone' => '+880 1700-000000',
            'email' => 'support@shopbd.test',
            'address' => 'House 12, Road 5, Dhanmondi, Dhaka 1205',
            'whatsapp' => '8801700000000',
            'opening_hours' => 'Sat – Thu, 10am – 8pm',
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'currency_position' => 'left',
            'decimals' => 0,
        ],

        'appearance' => [
            'primary_color' => '#d43d0b',
            'accent_color' => '#0f172a',
            'font' => 'inter',
            'radius' => 'lg',
            'card_style' => 'default',
            'custom_css' => null,
            'head_scripts' => null,
            'body_scripts' => null,
        ],

        'header' => [
            'topbar_enabled' => true,
            'topbar_text' => '🚚 Free delivery on orders over ৳2,000 · Use code WELCOME10 for 10% off your first order',
            'topbar_bg' => '#0f172a',
            'topbar_color' => '#ffffff',
            'topbar_links' => [
                ['label' => 'Track Order', 'url' => '/track-order'],
                ['label' => 'Help', 'url' => '/contact'],
            ],
            'layout' => 'classic',
            'sticky' => true,
            'show_search' => true,
            'search_placeholder' => 'Search for products, brands and more…',
            'show_category_menu' => true,
            'show_hotline' => true,
            'show_wishlist' => true,
            'show_account' => true,
            'menu' => [
                ['label' => 'Home', 'url' => '/', 'highlight' => false, 'children' => []],
                ['label' => 'Shop', 'url' => '/shop', 'highlight' => false, 'children' => []],
                ['label' => 'Hot Deals', 'url' => '/shop?on_sale=1', 'highlight' => true, 'children' => []],
                ['label' => 'New Arrivals', 'url' => '/shop?sort=latest', 'highlight' => false, 'children' => []],
                ['label' => 'Blog', 'url' => '/blog', 'highlight' => false, 'children' => []],
                ['label' => 'Contact', 'url' => '/contact', 'highlight' => false, 'children' => []],
            ],
        ],

        'footer' => [
            'style' => 'dark',
            'about' => 'Your trusted online shop in Bangladesh. 100% genuine products, cash on delivery all over the country and easy 7-day returns.',
            'columns' => [
                ['title' => 'Customer Care', 'links' => [
                    ['label' => 'Track Your Order', 'url' => '/track-order'],
                    ['label' => 'Contact Us', 'url' => '/contact'],
                    ['label' => 'Return & Refund', 'url' => '/return-policy'],
                    ['label' => 'Shipping Info', 'url' => '/shipping-policy'],
                ]],
                ['title' => 'Company', 'links' => [
                    ['label' => 'About Us', 'url' => '/about-us'],
                    ['label' => 'Blog', 'url' => '/blog'],
                    ['label' => 'Privacy Policy', 'url' => '/privacy-policy'],
                    ['label' => 'Terms & Conditions', 'url' => '/terms-and-conditions'],
                ]],
            ],
            'show_contact' => true,
            'show_newsletter' => true,
            'newsletter_title' => 'Get exclusive deals',
            'newsletter_text' => 'Subscribe and be the first to hear about new arrivals and flash sales.',
            'social' => [
                'facebook' => 'https://facebook.com',
                'instagram' => 'https://instagram.com',
                'youtube' => 'https://youtube.com',
                'tiktok' => null,
                'twitter' => null,
                'linkedin' => null,
            ],
            'payment_methods' => ['cod', 'bkash', 'nagad', 'visa', 'mastercard'],
            'payment_image' => null,
            'copyright' => '© {year} {site}. All rights reserved.',
        ],

        'mobile' => [
            'bottom_nav' => true,
        ],

        'seo' => [
            'meta_title' => 'Online Shopping in Bangladesh',
            'title_separator' => ' | ',
            'meta_description' => 'Shop genuine electronics, fashion, beauty and home products online in Bangladesh. Best prices, fast delivery and cash on delivery nationwide.',
            'meta_keywords' => 'online shopping bangladesh, ecommerce bd, cash on delivery',
            'og_image' => null,
            'google_verification' => null,
            'bing_verification' => null,
            'facebook_domain_verification' => null,
            'gtm_id' => null,
            'ga4_id' => null,
            'fb_pixel_id' => null,
            'discourage_indexing' => false,
            'robots_extra' => null,
            'organization_type' => 'OnlineStore',
        ],

        'catalog' => [
            'per_page' => 24,
            'new_days' => 21,
            'show_stock_count' => true,
            'low_stock_text' => 'Hurry! Only :count left in stock',
            'show_sold_count' => true,
            'reviews_enabled' => true,
            'reviews_auto_approve' => false,
            'related_count' => 10,
            'delivery_info' => 'Inside Dhaka: 1–2 days · Outside Dhaka: 2–4 days',
            'return_info' => 'Easy 7-day return & exchange',
        ],

        'checkout' => [
            'order_prefix' => 'ORD',
            'require_email' => false,
            'show_coupon' => true,
            'show_order_note' => true,
            'min_order' => 0,
            'free_shipping_over' => 2000,
            'capture_incomplete' => true,
            'admin_email' => null,
            'success_message' => 'Thank you! Our team will call you shortly to confirm your order.',
            'terms_text' => 'By placing this order you agree to our Terms & Conditions and Return Policy.',
            'cod_enabled' => true,
            'cod_label' => 'Cash on Delivery',
            'cod_note' => 'Pay with cash when your order arrives.',
            'bkash_enabled' => true,
            'bkash_number' => '01700-000000',
            'bkash_type' => 'Personal',
            'bkash_note' => 'Send Money to our bKash number, then enter your bKash number and Transaction ID below.',
            'nagad_enabled' => true,
            'nagad_number' => '01700-000000',
            'nagad_note' => 'Send Money to our Nagad number, then enter the Transaction ID below.',
            'bank_enabled' => false,
            'bank_details' => null,
        ],

        'conversion' => [
            'whatsapp_button' => true,
            'whatsapp_message' => 'Hi! I need help with an order.',
            'whatsapp_order' => true,
            'call_to_order' => true,
            'sticky_add_to_cart' => true,
            'buy_now' => true,
            'sales_popup' => true,
            'sales_popup_interval' => 18,
            'exit_popup' => true,
            'exit_popup_title' => 'Wait! Don’t miss 10% off',
            'exit_popup_text' => 'Use this code at checkout and save on your first order today.',
            'exit_popup_coupon' => 'WELCOME10',
            'exit_popup_image' => null,
            'recently_viewed' => true,
            'trust_badges' => [
                ['icon' => 'cash', 'text' => 'Cash on Delivery'],
                ['icon' => 'refresh', 'text' => '7-Day Easy Return'],
                ['icon' => 'shield', 'text' => '100% Genuine'],
                ['icon' => 'truck', 'text' => 'Fast Delivery'],
            ],
        ],
    ],

    'fonts' => [
        'system' => ['label' => 'System (fastest)', 'family' => null, 'css' => 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'],
        'inter' => ['label' => 'Inter', 'family' => 'Inter:wght@400;500;600;700;800', 'css' => '"Inter", ui-sans-serif, system-ui, sans-serif'],
        'poppins' => ['label' => 'Poppins', 'family' => 'Poppins:wght@400;500;600;700', 'css' => '"Poppins", ui-sans-serif, system-ui, sans-serif'],
        'dm-sans' => ['label' => 'DM Sans', 'family' => 'DM+Sans:wght@400;500;600;700', 'css' => '"DM Sans", ui-sans-serif, system-ui, sans-serif'],
        'manrope' => ['label' => 'Manrope', 'family' => 'Manrope:wght@400;500;600;700;800', 'css' => '"Manrope", ui-sans-serif, system-ui, sans-serif'],
        'hind-siliguri' => ['label' => 'Hind Siliguri (বাংলা)', 'family' => 'Hind+Siliguri:wght@400;500;600;700', 'css' => '"Hind Siliguri", ui-sans-serif, system-ui, sans-serif'],
    ],

    'radius' => [
        'none' => '0px', 'sm' => '0.25rem', 'md' => '0.5rem', 'lg' => '0.75rem', 'xl' => '1rem', '2xl' => '1.5rem',
    ],

    'districts' => [
        'Dhaka', 'Chattogram', 'Gazipur', 'Narayanganj', 'Sylhet', 'Rajshahi', 'Khulna', 'Barishal', 'Rangpur', 'Mymensingh', 'Cumilla',
        'Bagerhat', 'Bandarban', 'Barguna', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur', 'Chapai Nawabganj', 'Chuadanga',
        "Cox's Bazar", 'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokathi',
        'Jhenaidah', 'Joypurhat', 'Khagrachhari', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur',
        'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Naogaon', 'Narail', 'Narsingdi', 'Natore', 'Netrokona',
        'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rangamati', 'Satkhira', 'Shariatpur',
        'Sherpur', 'Sirajganj', 'Sunamganj', 'Tangail', 'Thakurgaon',
    ],
];
