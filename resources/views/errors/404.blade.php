@extends('errors.layout')

@section('title', 'Page not found')
@section('code', '404')
@section('heading', 'Page not found')
@section('message', 'The page you requested does not exist or may have moved.')

@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Back to website</a>
    <a class="btn ghost" href="{{ route('login') }}">ERP sign in</a>
@endsection
