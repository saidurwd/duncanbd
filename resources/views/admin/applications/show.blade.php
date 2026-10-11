@extends('layouts.admin')

@section('title', 'Application: '.$application->name)

@section('content')
    <div class="row g-4">
        <div class="col-lg-7"><div class="card"><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Vacancy</dt><dd class="col-sm-9">{{ $application->vacancy->title }}</dd>
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $application->name }}</dd>
                <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></dd>
                <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $application->phone }}</dd>
                <dt class="col-sm-3">CV</dt><dd class="col-sm-9"><a href="{{ route('admin.applications.cv', $application) }}">{{ $application->cv_original_name }}</a> ({{ Illuminate\Support\Number::fileSize($application->cv_size) }})</dd>
                <dt class="col-sm-3">Consent</dt><dd class="col-sm-9">{{ $application->consent_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</dd>
                <dt class="col-sm-3">Cover letter</dt><dd class="col-sm-9 pre-wrap text-break">{{ $application->cover_letter }}</dd>
            </dl>
        </div></div></div>
        <div class="col-lg-5">
            <form method="POST" action="{{ route('admin.applications.update', $application) }}" class="card"><div class="card-body">
                @csrf @method('PUT')
                <div class="mb-3"><label for="status" class="form-label">Pipeline status</label>
                    <select id="status" name="status" class="form-select">@foreach (App\Domain\Careers\Enums\ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($application->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                <div class="mb-3"><label for="internal_note" class="form-label">Internal note</label><textarea id="internal_note" name="internal_note" rows="4" class="form-control">{{ old('internal_note', $application->internal_note) }}</textarea></div>
                <button class="btn btn-primary">Save</button>
            </div></form>
        </div>
    </div>
    <a href="{{ route('admin.applications.index', ['vacancy' => $application->vacancy_id]) }}" class="btn btn-link mt-3">Back</a>
@endsection
