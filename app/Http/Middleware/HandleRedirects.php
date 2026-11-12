<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** SEO 301/302 redirects managed from the admin panel. */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->is('admin*', 'livewire*', 'storage/*', 'build/*')) {
            $path = '/'.trim($request->path(), '/');
            $path = $path === '/' ? '/' : rtrim($path, '/');

            if ($hit = (Redirect::map()[$path] ?? null)) {
                [$to, $code, $id] = $hit;
                Redirect::whereKey($id)->increment('hits');

                return redirect()->to(menu_url($to), $code);
            }
        }

        return $next($request);
    }
}
