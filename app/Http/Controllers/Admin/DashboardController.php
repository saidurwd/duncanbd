<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Careers\Enums\ApplicationStatus;
use App\Domain\Careers\Models\Application;
use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Models\Document;
use App\Domain\Engagement\Enums\EnquiryStatus;
use App\Domain\Engagement\Enums\SubscriberStatus;
use App\Domain\Engagement\Models\Enquiry;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Identity\Models\User;
use App\Domain\News\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $boxes = [];

        if ($user->can('pages.edit')) {
            $boxes[] = ['label' => 'Published pages', 'value' => Page::where('status', PageStatus::Published->value)->count(), 'icon' => 'bi-file-earmark-text', 'color' => 'primary', 'route' => route('admin.pages.index', ['status' => 'published'])];
            $boxes[] = ['label' => 'Awaiting review', 'value' => Page::where('status', PageStatus::InReview->value)->count(), 'icon' => 'bi-hourglass-split', 'color' => 'warning', 'route' => route('admin.pages.index', ['status' => 'in_review'])];
        }
        if ($user->can('news.edit')) {
            $boxes[] = ['label' => 'News stories', 'value' => Post::where('status', 'published')->count(), 'icon' => 'bi-newspaper', 'color' => 'info', 'route' => route('admin.posts.index')];
        }
        if ($user->can('documents.investor.manage') || $user->can('documents.general.manage')) {
            $boxes[] = ['label' => 'Published documents', 'value' => Document::where('status', 'published')->count(), 'icon' => 'bi-file-earmark-pdf', 'color' => 'success', 'route' => route('admin.documents.index')];
        }
        if ($user->can('enquiries.manage') || $user->can('enquiries.view')) {
            $boxes[] = ['label' => 'New enquiries', 'value' => Enquiry::where('status', EnquiryStatus::New->value)->count(), 'icon' => 'bi-envelope', 'color' => 'danger', 'route' => route('admin.enquiries.index', ['status' => 'new'])];
            $boxes[] = ['label' => 'Confirmed subscribers', 'value' => Subscriber::where('status', SubscriberStatus::Confirmed->value)->count(), 'icon' => 'bi-people', 'color' => 'secondary', 'route' => route('admin.subscribers.index')];
        }
        if ($user->can('careers.manage')) {
            $boxes[] = ['label' => 'New applications', 'value' => Application::where('status', ApplicationStatus::New->value)->count(), 'icon' => 'bi-briefcase', 'color' => 'primary', 'route' => route('admin.applications.index', ['status' => 'new'])];
        }
        if ($user->can('users.manage')) {
            $boxes[] = ['label' => 'Active users', 'value' => User::where('is_active', true)->count(), 'icon' => 'bi-person-gear', 'color' => 'dark', 'route' => route('admin.users.index')];
        }

        return view('admin.dashboard', [
            'boxes' => $boxes,
            'drafts' => $user->can('pages.edit') ? Page::whereIn('status', [PageStatus::Draft->value, PageStatus::InReview->value])->latest('updated_at')->limit(6)->get(['id', 'title', 'status', 'updated_at']) : collect(),
            'activity' => $user->can('audit.view') ? Activity::with('causer')->latest('id')->limit(8)->get() : collect(),
            'actions' => array_values(array_filter([
                $user->can('pages.edit') ? ['New page', route('admin.pages.create'), 'bi-file-earmark-plus'] : null,
                $user->can('news.edit') ? ['New story', route('admin.posts.create'), 'bi-newspaper'] : null,
                ($user->can('documents.investor.manage') || $user->can('documents.general.manage')) ? ['Upload document', route('admin.documents.create'), 'bi-upload'] : null,
                $user->can('media.manage') ? ['Add images', route('admin.media.index'), 'bi-images'] : null,
                $user->can('careers.manage') ? ['New vacancy', route('admin.vacancies.create'), 'bi-briefcase'] : null,
                $user->can('users.manage') ? ['Add user', route('admin.users.create'), 'bi-person-plus'] : null,
            ])),
        ]);
    }
}
