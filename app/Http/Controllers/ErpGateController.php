<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * /dhaka – sets the gate cookie for the parent domain and sends the visitor to a private
 * subdomain (e.g. an ERP). That subdomain's .htaccess refuses requests without the cookie.
 * Disabled (404) unless ERP_GATE_SECRET, ERP_URL and ERP_COOKIE_DOMAIN are set.
 */
class ErpGateController extends Controller
{
    public const COOKIE = 'gnw_erp_gate';

    public function __invoke(): RedirectResponse
    {
        $erp = config('services.erp');

        abort_if(blank($erp['secret']) || blank($erp['url']) || blank($erp['cookie_domain']), 404);

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
