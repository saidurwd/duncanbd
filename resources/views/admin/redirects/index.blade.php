@extends('layouts.admin')

@section('title', 'Redirects & 404s')

@section('content')
    <div class="card mb-4">
        <div class="card-header"><h2 class="card-title">Add redirect</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.redirects.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <label for="from_path" class="form-label">Old path</label>
                    <input id="from_path" name="from_path" value="{{ old('from_path', $prefill) }}" class="form-control" placeholder="/old/page.aspx" required>
                </div>
                <div class="col-md-4">
                    <label for="to_url" class="form-label">New URL</label>
                    <input id="to_url" name="to_url" value="{{ old('to_url') }}" class="form-control" placeholder="/new-page or https://…" required>
                </div>
                <div class="col-md-2">
                    <label for="status_code" class="form-label">Type</label>
                    <select id="status_code" name="status_code" class="form-select">
                        <option value="301">301 permanent</option>
                        <option value="302">302 temporary</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="GET" class="d-flex gap-2 mb-2">
                <label for="q" class="visually-hidden">Search</label>
                <input id="q" name="q" value="{{ $search }}" placeholder="Search old paths" class="form-control">
                <button class="btn btn-outline-secondary">Search</button>
            </form>
            <div class="card"><div class="table-responsive"><table class="table mb-0">
                <thead><tr><th>From</th><th>To</th><th>Code</th><th>Hits</th><th></th></tr></thead>
                <tbody>
                @forelse ($redirects as $redirect)
                    <tr>
                        <td><code>{{ $redirect->from_path }}</code>@if ($redirect->is_automatic) <span class="badge text-bg-info">auto</span>@endif</td>
                        <td><code>{{ $redirect->to_url }}</code></td>
                        <td>{{ $redirect->status_code }}</td>
                        <td>{{ $redirect->hits }}</td>
                        <td><form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No redirects.</td></tr>
                @endforelse
                </tbody>
            </table></div></div>
            <div class="mt-3">{{ $redirects->links() }}</div>
        </div>
        <div class="col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Top 404s</h2>
                <form method="POST" action="{{ route('admin.redirects.not-found.clear') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Clear</button></form>
            </div>
            <div class="card"><ul class="list-group list-group-flush">
                @forelse ($notFound as $entry)
                    <li class="list-group-item d-flex justify-content-between">
                        <span><code>{{ $entry->path }}</code></span>
                        <span><span class="badge text-bg-secondary">{{ $entry->hits }}</span>
                            <a href="{{ route('admin.redirects.index', ['from' => $entry->path]) }}" class="small">Redirect</a></span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Nothing logged.</li>
                @endforelse
            </ul></div>
        </div>
    </div>
@endsection
