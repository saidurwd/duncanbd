@extends('layouts.admin')

@section('title', $location->label())

@section('content')
    <form method="POST" action="{{ route('admin.menus.update', $location->value) }}" data-menu-editor>
        @csrf
        @method('PUT')
        <p class="text-muted">Items are shown in this order. To nest an item, set <em>Parent</em> to the number of a top-level item above it. Two levels maximum.</p>
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>#</th><th>Label</th><th>Page</th><th>or URL</th><th>Parent #</th><th>New tab</th><th>Active</th><th></th></tr></thead>
            <tbody data-rows>
            @foreach ($rows as $i => $row)
                @include('admin.menus._row', ['i' => $i, 'row' => $row, 'pages' => $pages])
            @endforeach
            </tbody>
        </table></div>
        <template data-row-template>@include('admin.menus._row', ['i' => '__INDEX__', 'row' => ['is_active' => true], 'pages' => $pages])</template>
        <button type="button" class="btn btn-outline-secondary" data-add-row>Add item</button>
        <button class="btn btn-primary">Save menu</button>
        <a href="{{ route('admin.menus.index') }}" class="btn btn-link">Back</a>
    </form>
@endsection
