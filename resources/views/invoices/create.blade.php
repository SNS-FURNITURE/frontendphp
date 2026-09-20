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
    'pageDescription' => 'Autosaves as a draft if you leave. Click Save to add it to the invoice list.',
    'salesOrderId' => $salesOrderId,
    'invoiceId' => null,
])
@endsection
