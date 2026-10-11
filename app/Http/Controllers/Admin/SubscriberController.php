<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Engagement\Enums\SubscriberStatus;
use App\Domain\Engagement\Models\Subscriber;
use App\Http\Controllers\Controller;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $status = SubscriberStatus::tryFrom((string) $request->query('status'));

        return view('admin.subscribers.index', [
            'subscribers' => Subscriber::when($status, fn ($q) => $q->where('status', $status->value))->latest('id')->paginate(50)->withQueryString(),
            'counts' => Subscriber::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** Only confirmed subscribers are ever exported for mailing. */
    public function export(): StreamedResponse
    {
        return Csv::download('subscribers-'.now()->format('Y-m-d').'.csv', ['email', 'confirmed_at', 'consent_at'],
            Subscriber::where('status', SubscriberStatus::Confirmed->value)->orderBy('id')->lazy()->map(fn ($s) => [$s->email, $s->confirmed_at, $s->consent_at]));
    }
}
