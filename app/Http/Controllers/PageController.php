<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->is_active, 404);

        seo()->title($page->meta_title ?: $page->title)
            ->description($page->seo_description)
            ->canonical($page->url)
            ->breadcrumbs([[$page->title, $page->url]]);

        return view('page', compact('page'));
    }
}
