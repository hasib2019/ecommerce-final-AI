<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * /dhaka – sets the gate cookie for *.gadgetnwatch.com and sends the visitor to the ERP.
 * The ERP's .htaccess refuses every request that doesn't carry this cookie.
 */
class ErpGateController extends Controller
{
    public const COOKIE = 'gnw_erp_gate';

    public function __invoke(): RedirectResponse
    {
        $erp = config('services.erp');

        abort_if(blank($erp['secret']), 404);

        return redirect()->away($erp['url'])
            ->withCookie(cookie(
                name: self::COOKIE,
                value: $erp['secret'],
                minutes: $erp['minutes'],
                path: '/',
                domain: $erp['cookie_domain'],
                secure: true,
                httpOnly: true,
                sameSite: 'lax',
            ))
            ->withHeaders([
                'Cache-Control' => 'no-store, private',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
    }
}
