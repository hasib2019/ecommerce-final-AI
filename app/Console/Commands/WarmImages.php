<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Support\Img;
use Illuminate\Console\Command;

/**
 * Pre-generates the responsive WebP thumbnails so the first visitor after a deploy
 * (or after importing products) never waits for image resizing.
 */
class WarmImages extends Command
{
    protected $signature = 'shop:warm-images';

    protected $description = 'Pre-generate responsive WebP thumbnails for products, categories and banners';

    public function handle(): int
    {
        $jobs = [];

        Product::query()->select(['id', 'thumbnail', 'gallery'])->each(function (Product $p) use (&$jobs) {
            foreach ($p->images as $i => $path) {
                $jobs[] = [$path, $i === 0 ? [160, 320, 480, 640, 960] : [160, 480, 960]];
            }
        });
        Category::query()->get(['image', 'banner'])->each(function ($c) use (&$jobs) {
            $jobs[] = [$c->image, [160, 320, 480]];
            $jobs[] = [$c->banner, [640, 960, 1600]];
        });
        foreach (Brand::query()->pluck('logo') as $logo) {
            $jobs[] = [$logo, [320]];
        }
        HomeSection::query()->get()->each(function (HomeSection $s) use (&$jobs) {
            $data = (array) $s->data;
            array_walk_recursive($data, function ($v, $k) use (&$jobs) {
                if (in_array($k, ['image', 'mobile_image', 'avatar'], true) && is_string($v)) {
                    $jobs[] = [$v, [640, 960, 1280, 1600]];
                }
            });
        });

        $jobs = array_filter($jobs, fn ($j) => filled($j[0]));
        $bar = $this->output->createProgressBar(count($jobs));

        foreach ($jobs as [$path, $sizes]) {
            foreach ($sizes as $w) {
                Img::url($path, $w);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Thumbnails ready.');

        return self::SUCCESS;
    }
}
