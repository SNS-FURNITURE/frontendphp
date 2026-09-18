@extends('errors.layout')

@section('title', 'Forbidden')
@section('code', '403')
@section('heading', 'Access denied')
@section('message', 'You do not have permission to view this page.')

@section('actions')
    @auth
        <a class="btn primary" href="{{ route('workspace') }}">Go to workspace</a>
    @else
        <a class="btn primary" href="{{ route('login') }}">ERP sign in</a>
    @endauth
    <a class="btn ghost" href="{{ url('/') }}">Back to website</a>
@endsection
