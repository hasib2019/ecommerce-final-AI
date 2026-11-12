<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        seo()->title('Contact Us')
            ->description('Get in touch with '.setting('general.site_name').'. Call '.setting('general.phone').' or send us a message.')
            ->breadcrumbs([['Contact', route('contact')]])
            ->schema(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'name' => 'Contact '.setting('general.site_name'),
                'url' => route('contact'),
            ]));

        return view('contact');
    }

    public function store(Request $request)
    {
        // Honeypot – bots fill every field.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thanks! We will get back to you soon.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:20', 'required_without:email'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        ContactMessage::create($data);

        return back()->with('success', 'Thanks! We will get back to you soon.');
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:120']]);
        Subscriber::firstOrCreate(['email' => strtolower($data['email'])]);

        $message = 'You are subscribed. Welcome aboard!';

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $message])
            : back()->with('success', $message);
    }
}
