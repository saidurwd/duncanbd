@extends('layouts.admin')

@section('title', 'Document categories')

@section('content')
    <div class="card mb-4"><div class="card-body">
        <form method="POST" action="{{ route('admin.documents.categories.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-5"><label for="name" class="form-label">New category</label><input id="name" name="name" class="form-control" required></div>
            <div class="col-md-2"><label for="sort_order" class="form-label">Order</label><input id="sort_order" type="number" name="sort_order" class="form-control" value="0"></div>
            <div class="col-md-3"><div class="form-check"><input id="is_investor" type="checkbox" name="is_investor" value="1" class="form-check-input"><label for="is_investor" class="form-check-label">Investor / statutory</label></div></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
        </form>
    </div></div>
    <div class="card"><table class="table mb-0">
        <thead><tr><th>Name</th><th>Slug</th><th>Investor</th><th>Documents</th><th></th></tr></thead>
        <tbody>
        @foreach ($categories as $c)
            <tr>
                <td colspan="3">
                    <form method="POST" action="{{ route('admin.documents.categories.update', $c) }}" class="d-flex gap-2 align-items-center">
                        @csrf @method('PUT')
                        <label class="visually-hidden" for="n{{ $c->id }}">Name</label>
                        <input id="n{{ $c->id }}" name="name" value="{{ $c->name }}" class="form-control form-control-sm">
                        <code>{{ $c->slug }}</code>
                        <div class="form-check"><input id="i{{ $c->id }}" type="checkbox" name="is_investor" value="1" class="form-check-input" @checked($c->is_investor)><label for="i{{ $c->id }}" class="form-check-label">Investor</label></div>
                        <button class="btn btn-sm btn-outline-primary">Save</button>
                    </form>
                </td>
                <td>{{ $c->documents_count }}</td>
                <td>@if ($c->documents_count === 0)<form method="POST" action="{{ route('admin.documents.categories.destroy', $c) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endsection
