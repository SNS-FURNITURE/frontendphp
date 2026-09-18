@extends('errors.layout')

@section('title', 'Session expired')
@section('code', '419')
@section('heading', 'Session expired')
@section('message', 'Your session expired for security. Refresh and try again, or sign in to the ERP.')

@section('actions')
    <a class="btn primary" href="{{ route('login') }}">ERP sign in</a>
    <a class="btn ghost" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Go back</a>
@endsection
