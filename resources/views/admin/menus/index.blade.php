@extends('layouts.admin')

@section('title', 'Menus')

@section('content')
    <div class="list-group">
        @foreach ($locations as $location)
            <a href="{{ route('admin.menus.edit', $location->value) }}" class="list-group-item list-group-item-action">{{ $location->label() }}</a>
        @endforeach
    </div>
@endsection
