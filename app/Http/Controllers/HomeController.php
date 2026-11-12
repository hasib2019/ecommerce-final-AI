<?php

namespace App\Http\Controllers;

use App\Services\HomeSectionResolver;
use App\Support\Seo;

class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = HomeSectionResolver::sections();

        seo()->canonical(url('/'))->image(setting('seo.og_image'));
        foreach (Seo::siteSchemas() as $schema) {
            seo()->schema($schema);
        }

        // Preload the first hero slide so it becomes the LCP element instantly.
        $hero = $sections->firstWhere('type', 'hero_slider');
        $lcp = $hero?->get('slides.0.image');

        return view('home', compact('sections', 'lcp'));
    }
}
