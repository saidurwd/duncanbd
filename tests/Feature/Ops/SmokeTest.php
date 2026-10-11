<?php

use App\Domain\Careers\Models\Vacancy;
use App\Domain\Engagement\Models\Enquiry;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\News\Models\Post;
use App\Mail\ApplicationReceived;
use App\Mail\ConfirmSubscription;
use App\Mail\EnquiryAcknowledgement;
use App\Mail\EnquiryReceived;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('renders every parameterless admin screen for a super admin without errors', function () {
    $admin = User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole(Role::SuperAdmin->value);
    $skip = ['admin.privacy.export']; // needs an email query string

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods()) && str_starts_with((string) $r->getName(), 'admin.') && ! str_contains($r->uri(), '{') && ! in_array($r->getName(), $skip));

    expect($routes->count())->toBeGreaterThan(15);

    foreach ($routes as $route) {
        $this->actingAs($admin)->get('/'.$route->uri())->assertSuccessful("{$route->getName()} should render");
    }
});

it('renders the create/edit screens of every content module', function () {
    $admin = User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole(Role::SuperAdmin->value);
    $post = Post::create(['type' => 'news', 'title' => 'P', 'slug' => 'p']);
    $vacancy = Vacancy::create(['title' => 'V', 'slug' => 'v', 'employment_type' => 'full_time']);
    $application = $vacancy->applications()->create(['name' => 'A', 'email' => 'a@example.com', 'cv_path' => 'x', 'cv_original_name' => 'x.pdf', 'cv_mime' => 'x', 'cv_size' => 1, 'consent_at' => now()]);
    Subscriber::create(['email' => 's@example.com', 'status' => 'confirmed']);

    foreach ([
        route('admin.posts.edit', $post), route('admin.vacancies.edit', $vacancy), route('admin.applications.show', $application),
        route('admin.subscribers.index', ['status' => 'confirmed']), route('admin.privacy.index', ['email' => 'a@example.com']),
        route('admin.content.create', 'estates'), route('admin.content.create', 'timeline'), route('admin.menus.edit', 'footer'),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertSuccessful("$url should render");
    }
});

it('renders every outgoing email template', function () {
    $enquiry = Enquiry::create(['category' => 'general', 'name' => "O'Brien & Sons", 'email' => 'a@example.com', 'message' => 'Hello <b>there</b> & welcome']);
    $vacancy = Vacancy::create(['title' => 'Estate Manager', 'slug' => 'em', 'employment_type' => 'full_time']);
    $application = $vacancy->applications()->create(['name' => 'Rahim', 'email' => 'r@example.com', 'cv_path' => 'x', 'cv_original_name' => 'x.pdf', 'cv_mime' => 'x', 'cv_size' => 1, 'consent_at' => now()]);
    $subscriber = Subscriber::create(['email' => 's@example.com']);

    $received = (new EnquiryReceived($enquiry))->render();
    expect($received)->toContain("O'Brien & Sons")->toContain('Hello <b>there</b> & welcome')->not->toContain('&#039;')->not->toContain('&amp;');
    expect((new EnquiryAcknowledgement($enquiry))->render())->toContain('Hello <b>there</b> & welcome');
    expect((new ApplicationReceived($application))->render())->toContain('Estate Manager')->toContain('Rahim');
    expect((new ApplicationReceived($application, forApplicant: true))->render())->toContain('Estate Manager');

    $confirm = (new ConfirmSubscription($subscriber))->render();
    expect($confirm)->toContain('/newsletter/confirm/'.$subscriber->id)->toContain('signature=')->toContain('/newsletter/unsubscribe/'.$subscriber->id);
});
