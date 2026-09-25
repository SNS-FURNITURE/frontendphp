@extends('layouts.app')

@section('title', $invoice->invoice_number)
@section('content_class', 'content-wide')

@section('content')
@include('invoices._editor', [
    'document' => $document,
    'readOnly' => false,
    'formAction' => route('invoices.update', $invoice),
    'formMethod' => 'PUT',
    'pageTitle' => $invoice->invoice_number,
    'pageDescription' => 'Autosaves while you edit. Click Save to move it to the order list.',
    'status' => $invoice->status,
    'invoiceId' => $invoice->id,
    'invoiceStatus' => $invoice->status,
    'customers' => $customers,
])
@endsection
