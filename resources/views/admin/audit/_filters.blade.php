@props(['action', 'users', 'modules' => null, 'events' => false, 'outcomes' => false, 'export' => null])
<form method="GET" action="{{ $action }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3"><label for="f-q" class="form-label small mb-1">Search</label><input id="f-q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Action, record or detail"></div>
        <div class="col-6 col-md-2"><label for="f-user" class="form-label small mb-1">User</label>
            <select id="f-user" name="user" class="form-select"><option value="">Anyone</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        @if ($modules)
            <div class="col-6 col-md-2"><label for="f-module" class="form-label small mb-1">Area</label>
                <select id="f-module" name="module" class="form-select"><option value="">All areas</option>@foreach ($modules as $m)<option value="{{ $m }}" @selected(request('module') === $m)>{{ ucfirst($m) }}</option>@endforeach</select></div>
        @endif
        @if ($events)
            <div class="col-6 col-md-2"><label for="f-event" class="form-label small mb-1">Action</label>
                <select id="f-event" name="event" class="form-select"><option value="">Any</option>@foreach (['created', 'updated', 'deleted'] as $e)<option value="{{ $e }}" @selected(request('event') === $e)>{{ ucfirst($e) }}</option>@endforeach</select></div>
        @endif
        @if ($outcomes)
            <div class="col-6 col-md-2"><label for="f-outcome" class="form-label small mb-1">Outcome</label>
                <select id="f-outcome" name="outcome" class="form-select"><option value="">All</option><option value="login" @selected(request('outcome') === 'login')>Signed in</option><option value="login failed" @selected(request('outcome') === 'login failed')>Failed</option><option value="logout" @selected(request('outcome') === 'logout')>Signed out</option></select></div>
        @endif
        <div class="col-6 col-md-2"><label for="f-from" class="form-label small mb-1">From</label><input id="f-from" type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-6 col-md-2"><label for="f-to" class="form-label small mb-1">To</label><input id="f-to" type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-primary">Filter</button>
            <a href="{{ $action }}" class="btn btn-outline-secondary">Reset</a>
            @if ($export)<a href="{{ $export }}" class="btn btn-outline-primary"><i class="bi bi-download" aria-hidden="true"></i> CSV</a>@endif
        </div>
    </div>
</form>
