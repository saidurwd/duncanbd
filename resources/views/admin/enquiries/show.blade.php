@extends('layouts.admin')

@section('title', 'Enquiry from '.$enquiry->name)

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $enquiry->category->label() }}</dd>
                    <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $enquiry->name }}</dd>
                    <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
                    <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $enquiry->phone }}</dd>
                    <dt class="col-sm-3">Organisation</dt><dd class="col-sm-9">{{ $enquiry->organisation }}</dd>
                    <dt class="col-sm-3">Received</dt><dd class="col-sm-9">{{ $enquiry->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-3">Message</dt><dd class="col-sm-9" class="text-break pre-wrap">{{ $enquiry->message }}</dd>
                </dl>
            </div></div>
        </div>
        <div class="col-lg-5">
            @can('enquiries.manage')
                <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="card"><div class="card-body">
                    @csrf @method('PUT')
                    <div class="mb-3"><label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">@foreach (App\Domain\Engagement\Enums\EnquiryStatus::cases() as $s)<option value="{{ $s->value }}" @selected($enquiry->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                    <div class="mb-3"><label for="assigned_to" class="form-label">Assigned to</label>
                        <select id="assigned_to" name="assigned_to" class="form-select"><option value="">Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($enquiry->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
                    <div class="mb-3"><label for="internal_note" class="form-label">Internal note</label><textarea id="internal_note" name="internal_note" rows="4" class="form-control">{{ old('internal_note', $enquiry->internal_note) }}</textarea></div>
                    <button class="btn btn-primary">Save</button>
                </div></form>
                <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}" class="mt-3">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete enquiry</button></form>
            @endcan
        </div>
    </div>
    <a href="{{ route('admin.enquiries.index') }}" class="btn btn-link mt-3">Back</a>
@endsection
