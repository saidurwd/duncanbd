<?php

namespace App\Http\Controllers\Site;

use App\Domain\Engagement\EnquiryRouter;
use App\Domain\Engagement\Enums\EnquiryCategory;
use App\Domain\Engagement\Models\Enquiry;
use App\Http\Controllers\Controller;
use App\Mail\EnquiryAcknowledgement;
use App\Mail\EnquiryReceived;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        return view('site.contact', [
            'categories' => EnquiryCategory::cases(),
            'selected' => $request->query('category'),
        ]);
    }

    public function store(Request $request, EnquiryRouter $router): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::enum(EnquiryCategory::class)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s.]+$/'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],
        ]);

        $enquiry = Enquiry::create([
            ...Arr::except($data, ['consent']),
            'name' => preg_replace('/\s+/', ' ', trim($data['name'])),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'consent_at' => now(),
        ]);

        Mail::to($router->addressFor($enquiry->category))->send(new EnquiryReceived($enquiry));
        Mail::to($enquiry->email)->send(new EnquiryAcknowledgement($enquiry));

        return redirect()->route('contact')->with('status', 'Thank you. Your message has been received and we have emailed you an acknowledgement.');
    }
}
