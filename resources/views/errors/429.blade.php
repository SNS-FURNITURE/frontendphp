@extends('errors.layout')

@section('title', 'Too many requests')
@section('code', '429')
@section('heading', 'Too many requests')
@section('message', 'You have sent too many requests. Please wait a moment and try again.')

@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Back to website</a>
    <a class="btn ghost" href="{{ route('login') }}">ERP sign in</a>
@endsection
