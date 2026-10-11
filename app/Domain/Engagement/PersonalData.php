<?php

namespace App\Domain\Engagement;

use App\Domain\Careers\Models\Application;
use App\Domain\Engagement\Enums\SubscriberStatus;
use App\Domain\Engagement\Models\Enquiry;
use App\Domain\Engagement\Models\Subscriber;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Data-subject access and erasure across everything we hold about an email address. */
class PersonalData
{
    /** @return array{enquiries: int, applications: int, subscriber: bool} */
    public function counts(string $email): array
    {
        $email = Str::lower(trim($email));

        return [
            'enquiries' => Enquiry::where('email', $email)->count(),
            'applications' => Application::where('email', $email)->count(),
            'subscriber' => Subscriber::where('email', $email)->exists(),
        ];
    }

    /** @return array<string, mixed> */
    public function export(string $email): array
    {
        $email = Str::lower(trim($email));

        return [
            'email' => $email,
            'exported_at' => now()->toIso8601String(),
            'enquiries' => Enquiry::where('email', $email)->get()->map(fn ($e) => $e->only(['category', 'name', 'phone', 'organisation', 'message', 'status', 'consent_at', 'created_at']))->all(),
            'applications' => Application::with('vacancy')->where('email', $email)->get()->map(fn ($a) => [
                'vacancy' => $a->vacancy->title, 'name' => $a->name, 'phone' => $a->phone, 'cover_letter' => $a->cover_letter,
                'cv_file' => $a->cv_original_name, 'status' => $a->status->value, 'consent_at' => $a->consent_at, 'created_at' => $a->created_at,
            ])->all(),
            'subscriber' => Subscriber::where('email', $email)->first()?->only(['status', 'consent_at', 'confirmed_at', 'unsubscribed_at']),
        ];
    }

    /** Deletes everything, including stored CVs. A subscriber who unsubscribed is kept as a bare suppression entry. */
    public function erase(string $email): void
    {
        $email = Str::lower(trim($email));

        Enquiry::where('email', $email)->delete();

        foreach (Application::where('email', $email)->get() as $application) {
            $this->deleteApplication($application);
        }

        Subscriber::where('email', $email)->where('status', '!=', SubscriberStatus::Unsubscribed->value)->delete();
    }

    public function deleteApplication(Application $application): void
    {
        Storage::disk('local')->delete($application->cv_path);
        $application->delete();
    }
}
