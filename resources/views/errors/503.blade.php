@extends('errors.layout')

@section('title', 'Unavailable')
@section('code', '503')
@section('heading', 'Service unavailable')
@section('message', 'The site is temporarily unavailable. Please check back shortly.')

@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Back to website</a>
@endsection
