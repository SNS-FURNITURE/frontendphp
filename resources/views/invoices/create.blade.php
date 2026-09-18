@extends('layouts.app')

@section('title', 'Create invoice')
@section('content_class', 'content-wide')

@section('content')
@include('invoices._editor', [
    'document' => $document,
    'readOnly' => false,
    'formAction' => route('invoices.store'),
    'formMethod' => 'POST',
    'pageTitle' => 'Create invoice',
    'pageDescription' => 'Edit like Word, then save as draft or issue.',
    'salesOrderId' => $salesOrderId,
])
@endsection
