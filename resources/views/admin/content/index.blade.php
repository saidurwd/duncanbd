@extends('layouts.admin')

@section('title', $def->label)

@section('content')
    <div class="mb-3"><a href="{{ route('admin.content.create', $def->key) }}" class="btn btn-primary">Add</a></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr>@foreach ($def->columns as $heading)<th>{{ $heading }}</th>@endforeach<th></th></tr></thead>
        <tbody>
        @forelse ($records as $record)
            <tr>
                @foreach ($def->columns as $attr => $heading)
                    <td>@if ($loop->first)<a href="{{ route('admin.content.edit', [$def->key, $record->id]) }}">{{ $record->$attr }}</a>@else{{ $record->$attr }}@endif</td>
                @endforeach
                <td>@if (array_key_exists('is_active', $record->getAttributes()) && ! $record->is_active)<span class="badge text-bg-warning">Hidden</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="{{ count($def->columns) + 1 }}" class="text-muted">Nothing here yet.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $records->links() }}</div>
@endsection
