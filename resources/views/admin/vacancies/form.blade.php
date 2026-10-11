@extends('layouts.admin')

@section('title', $vacancy->exists ? 'Edit vacancy' : 'New vacancy')

@section('content')
    @if ($vacancy->exists)
        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="badge text-bg-secondary fs-6">{{ ucfirst($vacancy->status->value) }}</span>
            @if ($vacancy->status->value !== 'published')<form method="POST" action="{{ route('admin.vacancies.publish', $vacancy) }}">@csrf<button class="btn btn-sm btn-outline-primary">Publish</button></form>
            @else<form method="POST" action="{{ route('admin.vacancies.close', $vacancy) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Close now</button></form>@endif
            <a href="{{ route('admin.applications.index', ['vacancy' => $vacancy->id]) }}" class="btn btn-sm btn-link">Applications</a>
        </div>
    @endif
    <form method="POST" action="{{ $vacancy->exists ? route('admin.vacancies.update', $vacancy) : route('admin.vacancies.store') }}" class="row g-3">
        @csrf @if ($vacancy->exists) @method('PUT') @endif
        <div class="col-md-8"><label for="title" class="form-label">Title</label><input id="title" name="title" value="{{ old('title', $vacancy->title) }}" class="form-control" required></div>
        <div class="col-md-4"><label for="slug" class="form-label">Slug</label><input id="slug" name="slug" value="{{ old('slug', $vacancy->slug) }}" class="form-control" placeholder="auto"></div>
        <div class="col-md-3"><label for="department" class="form-label">Department</label><input id="department" name="department" value="{{ old('department', $vacancy->department) }}" class="form-control"></div>
        <div class="col-md-3"><label for="location" class="form-label">Location</label><input id="location" name="location" value="{{ old('location', $vacancy->location) }}" class="form-control"></div>
        <div class="col-md-3"><label for="employment_type" class="form-label">Type</label>
            <select id="employment_type" name="employment_type" class="form-select">@foreach (App\Domain\Careers\Enums\EmploymentType::cases() as $t)<option value="{{ $t->value }}" @selected(old('employment_type', $vacancy->employment_type->value) === $t->value)>{{ $t->label() }}</option>@endforeach</select></div>
        <div class="col-md-3"><label for="closing_date" class="form-label">Closing date</label><input id="closing_date" type="date" name="closing_date" value="{{ old('closing_date', $vacancy->closing_date?->format('Y-m-d')) }}" class="form-control"></div>
        <div class="col-12"><label for="description" class="form-label">Description (HTML: h2–h4, p, lists, links)</label><textarea id="description" name="description" rows="8" class="form-control">{{ old('description', $vacancy->description) }}</textarea></div>
        <div class="col-12"><label for="requirements" class="form-label">Requirements</label><textarea id="requirements" name="requirements" rows="6" class="form-control">{{ old('requirements', $vacancy->requirements) }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.vacancies.index') }}" class="btn btn-link">Back</a></div>
    </form>
    @if ($vacancy->exists)<form method="POST" action="{{ route('admin.vacancies.destroy', $vacancy) }}" class="mt-4">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Move to trash</button></form>@endif
@endsection
