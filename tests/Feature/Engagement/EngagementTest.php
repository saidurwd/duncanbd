<?php

use App\Domain\Careers\Enums\ApplicationStatus;
use App\Domain\Careers\Models\Application;
use App\Domain\Careers\Models\Vacancy;
use App\Domain\Cms\Models\Page;
use App\Domain\Engagement\Enums\SubscriberStatus;
use App\Domain\Engagement\Models\Enquiry;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Settings\SettingsRepository;
use App\Mail\ApplicationReceived;
use App\Mail\ConfirmSubscription;
use App\Mail\EnquiryAcknowledgement;
use App\Mail\EnquiryReceived;
use App\Support\MailConfigurator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Mail::fake();
    Storage::fake('local');
    RateLimiter::clear('forms');
});

/** A valid time-trap value issued 10 seconds ago. */
function ts(int $secondsAgo = 10): string
{
    test()->travel(-1)->seconds(); // no-op safeguard for clock-based tests
    $issued = Crypt::encryptString((string) (now()->timestamp - $secondsAgo));
    test()->travelBack();

    return $issued;
}

function contactPayload(array $o = []): array
{
    return array_merge([
        'category' => 'investor', 'name' => "Jane O'Brien", 'email' => 'jane@example.com',
        'message' => 'I would like to know about the AGM date.', 'consent' => 1, '_ts' => ts(),
    ], $o);
}

function staff(Role $role): User
{
    return User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole($role->value);
}

function openVacancy(array $o = []): Vacancy
{
    return Vacancy::create(array_merge(['title' => 'Estate Manager', 'slug' => 'estate-manager', 'status' => 'published', 'published_at' => now()->subDay(), 'employment_type' => 'full_time', 'description' => '<p>Lead an estate.</p>', 'location' => 'Sylhet'], $o));
}

// ---- Enquiries -------------------------------------------------------------

it('stores an enquiry, routes it by category and acknowledges the sender', function () {
    $settings = app(SettingsRepository::class);
    $settings->set('enquiries', 'route_investor', 'ir@duncanbd.com');
    $settings->set('enquiries', 'route_general', 'info@duncanbd.com');

    $this->post('/contact', contactPayload())->assertRedirect()->assertSessionHasNoErrors();

    $enquiry = Enquiry::firstOrFail();
    expect($enquiry->name)->toBe("Jane O'Brien")->and($enquiry->status->value)->toBe('new')->and($enquiry->consent_at)->not->toBeNull();

    Mail::assertQueued(EnquiryReceived::class, fn ($m) => $m->hasTo('ir@duncanbd.com') && $m->hasReplyTo('jane@example.com'));
    Mail::assertQueued(EnquiryAcknowledgement::class, fn ($m) => $m->hasTo('jane@example.com'));
});

it('falls back to the general inbox for categories without a rule', function () {
    app(SettingsRepository::class)->set('enquiries', 'route_general', 'info@duncanbd.com');

    $this->post('/contact', contactPayload(['category' => 'media']));

    Mail::assertQueued(EnquiryReceived::class, fn ($m) => $m->hasTo('info@duncanbd.com'));
});

it('validates the contact form', function () {
    $this->post('/contact', contactPayload(['email' => 'nope', 'message' => 'short', 'consent' => null, 'category' => 'bogus', 'phone' => 'call me <b>']))
        ->assertSessionHasErrors(['email', 'message', 'consent', 'category', 'phone']);

    expect(Enquiry::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('silently drops honeypot and too-fast submissions', function () {
    $this->post('/contact', contactPayload(['website' => 'http://spam.example']))->assertRedirect();
    $this->post('/contact', contactPayload(['_ts' => ts(0)]))->assertRedirect();            // too fast
    $this->post('/contact', contactPayload(['_ts' => 'garbage']))->assertRedirect();         // forged
    $this->post('/contact', contactPayload(['_ts' => ts(8000)]))->assertRedirect();          // stale

    expect(Enquiry::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('rate limits form posts per IP', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/contact', contactPayload())->assertRedirect();
    }
    $this->post('/contact', contactPayload())->assertStatus(429);
});

it('requires a Turnstile token when keys are configured', function () {
    config(['services.turnstile.secret' => 's', 'services.turnstile.site_key' => 'k']);
    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

    $this->post('/contact', contactPayload())->assertSessionHasErrors('captcha');
    $this->post('/contact', contactPayload(['cf-turnstile-response' => 'tok']))->assertSessionHasErrors('captcha');

    $this->flushSession();   // earlier flashed errors would otherwise linger in the test session
    $this->post('/contact', contactPayload(['cf-turnstile-response' => 'tok']))->assertSessionHasNoErrors();
    expect(Enquiry::count())->toBe(1);
});

it('renders the contact form with the guard fields and CSRF protection', function () {
    $this->get('/contact')->assertOk()->assertSee('name="_ts"', false)->assertSee('name="website"', false)->assertSee('name="_token"', false);
});

it('lets staff triage enquiries but only managers change them', function () {
    $enquiry = Enquiry::create(['category' => 'general', 'name' => 'A', 'email' => 'a@example.com', 'message' => 'Hello there friend']);

    $this->actingAs(staff(Role::Viewer))->get(route('admin.enquiries.index'))->assertOk()->assertSee('a@example.com');
    $this->actingAs(staff(Role::Viewer))->put(route('admin.enquiries.update', $enquiry), ['status' => 'resolved'])->assertForbidden();

    $editor = staff(Role::Editor);
    $this->actingAs($editor)->put(route('admin.enquiries.update', $enquiry), ['status' => 'in_progress', 'assigned_to' => $editor->id, 'internal_note' => 'on it'])->assertSessionHasNoErrors();
    expect($enquiry->fresh()->status->value)->toBe('in_progress')->and($enquiry->fresh()->assigned_to)->toBe($editor->id);

    $this->actingAs(staff(Role::Author))->get(route('admin.enquiries.index'))->assertForbidden();
});

it('keeps personal data out of the audit log', function () {
    $enquiry = Enquiry::create(['category' => 'general', 'name' => 'Secret Name', 'email' => 'secret@example.com', 'message' => 'private message here']);
    $enquiry->update(['status' => 'resolved']);

    $log = json_encode(Activity::where('log_name', 'enquiries')->get()->toArray());
    expect($log)->not->toContain('Secret Name')->not->toContain('secret@example.com')->not->toContain('private message');
});

// ---- Careers ---------------------------------------------------------------

it('lists open vacancies, hides drafts/closed, and emits JobPosting data', function () {
    openVacancy();
    openVacancy(['title' => 'Draft job', 'slug' => 'draft-job', 'status' => 'draft']);
    openVacancy(['title' => 'Past job', 'slug' => 'past-job', 'closing_date' => now()->subDay()->toDateString()]);

    $this->get('/careers/vacancies')->assertOk()->assertSee('Estate Manager')->assertDontSee('Draft job')->assertDontSee('Past job');
    $this->get('/careers/vacancies/estate-manager')->assertOk()->assertSee('"@type":"JobPosting"', false)->assertSee('Apply for this job');
    $this->get('/careers/vacancies/draft-job')->assertNotFound();
    $this->get('/careers/vacancies/past-job')->assertOk()->assertSee('now closed')->assertDontSee('JobPosting');
});

it('accepts an application, stores the CV privately and notifies HR and the candidate', function () {
    app(SettingsRepository::class)->set('enquiries', 'hr_email', 'hr@duncanbd.com');
    $vacancy = openVacancy();

    $this->post('/careers/vacancies/estate-manager/apply', [
        'name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'consent' => 1, '_ts' => ts(),
        'cv' => UploadedFile::fake()->createWithContent('My CV.pdf', "%PDF-1.4\n%cv\n"),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $application = Application::firstOrFail();
    expect($application->vacancy_id)->toBe($vacancy->id)->and($application->status)->toBe(ApplicationStatus::New)
        ->and($application->cv_path)->not->toContain('CV')->and($application->cv_original_name)->toBe('My CV.pdf');
    Storage::disk('local')->assertExists($application->cv_path);
    Mail::assertQueued(ApplicationReceived::class, fn ($m) => $m->hasTo('hr@duncanbd.com') && ! $m->forApplicant);
    Mail::assertQueued(ApplicationReceived::class, fn ($m) => $m->hasTo('rahim@example.com') && $m->forApplicant);
});

it('rejects bad CV files and applications to closed vacancies', function () {
    openVacancy();
    $base = ['name' => 'X Y', 'email' => 'x@example.com', 'consent' => 1, '_ts' => ts()];

    $this->post('/careers/vacancies/estate-manager/apply', $base + ['cv' => UploadedFile::fake()->create('cv.exe', 10)])->assertSessionHasErrors('cv');
    $this->post('/careers/vacancies/estate-manager/apply', $base + ['cv' => UploadedFile::fake()->create('cv.php', 10, 'application/x-php')])->assertSessionHasErrors('cv');
    $this->post('/careers/vacancies/estate-manager/apply', $base + ['cv' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('cv');
    $this->post('/careers/vacancies/estate-manager/apply', array_diff_key($base, ['consent' => 1]) + ['cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])->assertSessionHasErrors('consent');

    openVacancy(['slug' => 'closed', 'closing_date' => now()->subDay()->toDateString()]);
    $this->get('/careers/vacancies/closed/apply')->assertNotFound();
    $this->post('/careers/vacancies/closed/apply', $base + ['cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])->assertNotFound();

    expect(Application::count())->toBe(0);
});

it('treats the closing day as still open', function () {
    openVacancy(['closing_date' => now('Asia/Dhaka')->toDateString()]);

    $this->get('/careers/vacancies/estate-manager/apply')->assertOk();
});

it('serves CVs only to HR through the authenticated route and logs the download', function () {
    $vacancy = openVacancy();
    Storage::disk('local')->put('careers/2026/10/x.pdf', 'pdf-bytes');
    $application = $vacancy->applications()->create(['name' => 'A', 'email' => 'a@example.com', 'cv_path' => 'careers/2026/10/x.pdf', 'cv_original_name' => 'cv.pdf', 'cv_mime' => 'application/pdf', 'cv_size' => 9, 'consent_at' => now()]);

    $this->get(route('admin.applications.cv', $application))->assertRedirect('/login');
    $this->actingAs(staff(Role::Editor))->get(route('admin.applications.cv', $application))->assertForbidden();
    $this->actingAs(staff(Role::Hr))->get(route('admin.applications.cv', $application))->assertOk()->assertDownload('cv.pdf');

    expect(Activity::where('description', 'cv downloaded')->count())->toBe(1);
    $this->get('/storage/careers/2026/10/x.pdf')->assertNotFound();   // never on the public disk
});

it('lets HR manage vacancies, the pipeline and CSV export (formula-safe)', function () {
    $hr = staff(Role::Hr);
    $this->actingAs($hr)->post(route('admin.vacancies.store'), ['title' => 'Factory Engineer', 'employment_type' => 'full_time', 'description' => '<p>x</p><script>y()</script>', 'closing_date' => now()->addMonth()->toDateString()])->assertSessionHasNoErrors();
    $vacancy = Vacancy::firstOrFail();
    expect($vacancy->status->value)->toBe('draft')->and($vacancy->description)->not->toContain('<script');

    $this->actingAs($hr)->post(route('admin.vacancies.publish', $vacancy));
    $this->get('/careers/vacancies/factory-engineer')->assertOk();

    $application = $vacancy->applications()->create(['name' => '=HYPERLINK("http://evil")', 'email' => 'a@example.com', 'cv_path' => 'x', 'cv_original_name' => 'x', 'cv_mime' => 'x', 'cv_size' => 1, 'consent_at' => now()]);
    $this->actingAs($hr)->put(route('admin.applications.update', $application), ['status' => 'shortlisted']);
    expect($application->fresh()->status)->toBe(ApplicationStatus::Shortlisted);

    $csv = $this->actingAs($hr)->get(route('admin.applications.export'))->assertOk()->streamedContent();
    expect($csv)->toContain("'=HYPERLINK")->and($csv)->not->toContain(',=HYPERLINK');

    $this->actingAs(staff(Role::Editor))->get(route('admin.vacancies.index'))->assertForbidden();
});

it('closes vacancies past their deadline via the scheduler command', function () {
    $old = openVacancy(['closing_date' => now()->subDays(2)->toDateString()]);
    $live = openVacancy(['slug' => 'live', 'closing_date' => now()->addDays(2)->toDateString()]);

    $this->artisan('careers:close-expired')->assertSuccessful();

    expect($old->fresh()->status->value)->toBe('archived')->and($old->fresh()->closed_at)->not->toBeNull()->and($live->fresh()->status->value)->toBe('published');
});

// ---- Newsletter ------------------------------------------------------------

it('runs the double opt-in: pending until the confirm button is pressed', function () {
    $this->post('/newsletter', ['email' => 'Reader@Example.com', 'consent' => 1, '_ts' => ts()])->assertRedirect(route('newsletter.thanks'));

    $subscriber = Subscriber::firstOrFail();
    expect($subscriber->email)->toBe('reader@example.com')->and($subscriber->status)->toBe(SubscriberStatus::Pending);
    Mail::assertQueued(ConfirmSubscription::class);

    $url = URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $subscriber->id]);
    $this->get($url)->assertOk();                                       // a scanner opening the link changes nothing
    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Pending);

    $this->post($url)->assertOk()->assertSee('confirmed');
    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Confirmed)->and($subscriber->fresh()->confirmed_at)->not->toBeNull();
});

it('rejects unsigned, tampered and expired newsletter links', function () {
    $subscriber = Subscriber::create(['email' => 'a@example.com']);

    $this->post(route('newsletter.confirm.store', $subscriber))->assertForbidden();
    $this->post(URL::temporarySignedRoute('newsletter.confirm', now()->subMinute(), ['subscriber' => $subscriber->id]))->assertForbidden();
    $other = Subscriber::create(['email' => 'b@example.com']);
    $tampered = str_replace('/'.$subscriber->id.'?', '/'.$other->id.'?', URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $subscriber->id]));
    $this->post($tampered)->assertForbidden();
    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Pending);
});

it('unsubscribes through a signed link and never re-confirms an unsubscribed address silently', function () {
    $subscriber = Subscriber::create(['email' => 'a@example.com', 'status' => 'confirmed', 'confirmed_at' => now()]);

    $this->post(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]))->assertOk();
    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Unsubscribed);

    // A stale confirmation link cannot resurrect an unsubscribed address.
    $this->post(URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $subscriber->id]));
    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Unsubscribed);
});

it('gives the same answer for new and existing addresses and caps mail per address', function () {
    Subscriber::create(['email' => 'known@example.com', 'status' => 'confirmed']);

    $a = $this->post('/newsletter', ['email' => 'known@example.com', 'consent' => 1, '_ts' => ts()]);
    $b = $this->post('/newsletter', ['email' => 'new@example.com', 'consent' => 1, '_ts' => ts()]);
    expect($a->getSession()->get('status'))->toBe($b->getSession()->get('status'));
    Mail::assertQueuedCount(1);                                         // none for the already-confirmed address

    foreach (range(1, 5) as $i) {
        RateLimiter::clear('forms');
        $this->post('/newsletter', ['email' => 'victim@example.com', 'consent' => 1, '_ts' => ts()]);
    }
    Mail::assertQueued(ConfirmSubscription::class, 1 + 3);              // 1 for new@, max 3 per hour for victim@
});

it('answers the newsletter band with JSON for in-page submission', function () {
    $this->postJson('/newsletter', ['email' => 'a@example.com', 'consent' => 1, '_ts' => ts()])->assertOk()->assertJsonStructure(['message']);
    $this->postJson('/newsletter', ['email' => 'bad', 'consent' => 1, '_ts' => ts()])->assertStatus(422)->assertJsonValidationErrors('email');
    $this->postJson('/newsletter', ['email' => 'a@example.com', 'consent' => 1, '_ts' => 'bot'])->assertOk();   // bots get the same answer
});

it('requires consent and a valid email to subscribe', function () {
    $this->post('/newsletter', ['email' => 'bad', '_ts' => ts()])->assertSessionHasErrors(['email', 'consent']);
});

it('serves a fresh CSRF token and time-trap value to cached pages, uncached', function () {
    $response = $this->getJson('/forms/token')->assertOk()->assertJsonStructure(['csrf', 'ts']);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('exports only confirmed subscribers', function () {
    Subscriber::create(['email' => 'yes@example.com', 'status' => 'confirmed', 'confirmed_at' => now()]);
    Subscriber::create(['email' => 'pending@example.com', 'status' => 'pending']);
    Subscriber::create(['email' => 'gone@example.com', 'status' => 'unsubscribed']);

    $csv = $this->actingAs(staff(Role::Editor))->get(route('admin.subscribers.export'))->assertOk()->streamedContent();

    expect($csv)->toContain('yes@example.com')->not->toContain('pending@')->not->toContain('gone@');
    $this->actingAs(staff(Role::Viewer))->get(route('admin.subscribers.export'))->assertForbidden();
});

it('shows the newsletter band on public pages without embedding a token', function () {
    Page::create(['title' => 'About', 'slug' => 'about', 'path' => 'about', 'status' => 'published', 'published_at' => now()->subDay(), 'blocks' => []]);

    $html = $this->get('/about')->assertOk()->assertSee('Receive news', false)->getContent();

    expect($html)->toContain('data-form-csrf')->and($html)->toMatch('/data-form-csrf/');
    preg_match('/name="_token" value="([^"]*)" data-form-csrf/', $html, $m);
    expect($m[1] ?? 'x')->toBe('');
});

// ---- Retention & data-subject rights --------------------------------------

it('prunes personal data past its retention period, including CV files', function () {
    $closed = openVacancy(['status' => 'archived', 'closed_at' => now()->subMonths(13), 'closing_date' => now()->subMonths(14)->toDateString()]);
    $recent = openVacancy(['slug' => 'recent', 'status' => 'archived', 'closed_at' => now()->subMonths(2)]);
    Storage::disk('local')->put('careers/old.pdf', 'x');
    Storage::disk('local')->put('careers/new.pdf', 'x');
    $mk = fn ($vac, $path) => $vac->applications()->create(['name' => 'A', 'email' => 'a@example.com', 'cv_path' => $path, 'cv_original_name' => 'cv.pdf', 'cv_mime' => 'x', 'cv_size' => 1, 'consent_at' => now()]);
    $mk($closed, 'careers/old.pdf');
    $mk($recent, 'careers/new.pdf');

    Enquiry::create(['category' => 'general', 'name' => 'Old', 'email' => 'o@example.com', 'message' => 'old message text']);
    Enquiry::query()->update(['created_at' => now()->subMonths(25)]);
    Enquiry::create(['category' => 'general', 'name' => 'New', 'email' => 'n@example.com', 'message' => 'new message text']);

    Subscriber::create(['email' => 'stale@example.com', 'status' => 'pending']);
    Subscriber::query()->update(['updated_at' => now()->subDays(8)]);
    Subscriber::create(['email' => 'fresh@example.com', 'status' => 'pending']);

    $this->artisan('privacy:prune')->assertSuccessful();

    expect(Application::count())->toBe(1)->and(Enquiry::count())->toBe(1)->and(Subscriber::count())->toBe(1);
    Storage::disk('local')->assertMissing('careers/old.pdf');
    Storage::disk('local')->assertExists('careers/new.pdf');
});

it('exports and erases everything held about an address', function () {
    $vacancy = openVacancy();
    Storage::disk('local')->put('careers/p.pdf', 'x');
    Enquiry::create(['category' => 'general', 'name' => 'P', 'email' => 'person@example.com', 'message' => 'a long enough message']);
    $vacancy->applications()->create(['name' => 'P', 'email' => 'person@example.com', 'cv_path' => 'careers/p.pdf', 'cv_original_name' => 'cv.pdf', 'cv_mime' => 'x', 'cv_size' => 1, 'consent_at' => now()]);
    Subscriber::create(['email' => 'person@example.com', 'status' => 'confirmed']);
    Enquiry::create(['category' => 'general', 'name' => 'Other', 'email' => 'other@example.com', 'message' => 'someone elses message']);
    $admin = staff(Role::SiteAdmin);

    $export = $this->actingAs($admin)->get(route('admin.privacy.export', ['email' => 'Person@Example.com']))->assertOk()->json();
    expect($export['enquiries'])->toHaveCount(1)->and($export['applications'])->toHaveCount(1)->and($export['subscriber']['status'])->toBe('confirmed');

    $this->actingAs($admin)->delete(route('admin.privacy.erase'), ['email' => 'person@example.com', 'confirm' => 'nope'])->assertSessionHasErrors('confirm');
    $this->actingAs($admin)->delete(route('admin.privacy.erase'), ['email' => 'person@example.com', 'confirm' => 'ERASE'])->assertRedirect();

    expect(Enquiry::count())->toBe(1)->and(Application::count())->toBe(0)->and(Subscriber::count())->toBe(0);
    Storage::disk('local')->assertMissing('careers/p.pdf');
    $this->actingAs(staff(Role::Editor))->get(route('admin.privacy.index'))->assertForbidden();
});

it('applies SMTP and sender settings from the database', function () {
    $settings = app(SettingsRepository::class);
    $settings->set('mail', 'host', 'smtp.example.com');
    $settings->set('mail', 'port', 2525);
    $settings->set('mail', 'password', 'smtp-secret', encrypted: true);
    $settings->set('mail', 'from_address', 'noreply@duncanbd.com');

    app(MailConfigurator::class)->apply();

    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.com')
        ->and(config('mail.mailers.smtp.password'))->toBe('smtp-secret')
        ->and(config('mail.from.address'))->toBe('noreply@duncanbd.com');
});
