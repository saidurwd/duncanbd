<?php

namespace App\Http\Controllers\Site;

use App\Domain\Engagement\Enums\SubscriberStatus;
use App\Domain\Engagement\Models\Subscriber;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmSubscription;
use App\Support\FormGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function show(): View
    {
        return view('site.newsletter.subscribe');
    }

    public function thanks(): View
    {
        return view('site.newsletter.done', ['message' => session('status', 'Thank you.')]);
    }

    /** Cached public pages carry no session or token; the sign-up band fetches fresh ones on first use. */
    public function token(FormGuard $guard): JsonResponse
    {
        return response()->json(['csrf' => csrf_token(), 'ts' => $guard->issueTimestamp()])
            ->header('Cache-Control', 'no-store, private');
    }

    public function subscribe(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'consent' => ['accepted'],
        ]);

        $email = Str::lower(trim($data['email']));

        // Same answer whatever happens (no account enumeration) and a per-address cap (no mail-bombing a victim).
        $message = 'Thank you. If this address can be subscribed, we have sent a confirmation email — please click the link in it.';
        $generic = $request->expectsJson() ? response()->json(['message' => $message]) : redirect()->route('newsletter.thanks')->with('status', $message);

        if (! RateLimiter::attempt('newsletter:'.sha1($email), 3, fn () => true, 3600)) {
            return $generic;
        }

        $subscriber = Subscriber::firstOrNew(['email' => $email]);

        if ($subscriber->status === SubscriberStatus::Confirmed) {
            return $generic;
        }

        $subscriber->fill(['status' => SubscriberStatus::Pending, 'consent_at' => now(), 'ip' => $request->ip(), 'unsubscribed_at' => null])->save();

        Mail::to($email)->send(new ConfirmSubscription($subscriber));

        return $generic;
    }

    /** Link scanners prefetch GET links, so state only changes on the POST below. */
    public function confirmPage(Subscriber $subscriber): View
    {
        return view('site.newsletter.action', ['subscriber' => $subscriber, 'action' => 'confirm']);
    }

    public function confirm(Subscriber $subscriber): View
    {
        if ($subscriber->status !== SubscriberStatus::Unsubscribed) {
            $subscriber->update(['status' => SubscriberStatus::Confirmed, 'confirmed_at' => now()]);
        }

        return view('site.newsletter.done', ['message' => 'Your subscription is confirmed. Thank you.']);
    }

    public function unsubscribePage(Subscriber $subscriber): View
    {
        return view('site.newsletter.action', ['subscriber' => $subscriber, 'action' => 'unsubscribe']);
    }

    public function unsubscribe(Subscriber $subscriber): View
    {
        $subscriber->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);

        return view('site.newsletter.done', ['message' => 'You have been unsubscribed and will receive no further emails.']);
    }
}
