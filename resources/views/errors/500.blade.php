@extends('errors.layout')

@section('title', 'Server error')
@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', 'An unexpected error occurred. Please try again in a moment.')

@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Back to website</a>
    <a class="btn ghost" href="{{ route('login') }}">ERP sign in</a>
@endsection
