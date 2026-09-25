@extends('layouts.app')

@section('title', 'Create order')
@section('content_class', 'content-wide')

@section('content')
@include('invoices._editor', [
    'document' => $document,
    'readOnly' => false,
    'formAction' => route('invoices.store'),
    'formMethod' => 'POST',
    'pageTitle' => 'Create order',
    'pageDescription' => 'Autosaves as a draft if you leave. Click Save to add it to the order list.',
    'salesOrderId' => $salesOrderId,
    'invoiceId' => null,
    'customers' => $customers,
])
@endsection
