@extends('layouts.app')

@section('title', 'Create invoice')

@section('content')
@include('invoices._form', [
    'document' => $document,
    'formAction' => route('invoices.store'),
    'formMethod' => 'POST',
    'pageTitle' => 'Create invoice',
    'pageDescription' => 'Fill in the form. After you save, the invoice opens as a Word-style document.',
    'salesOrderId' => $salesOrderId,
])
@endsection
