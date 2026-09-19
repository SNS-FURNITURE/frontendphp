@extends('layouts.app')

@section('title', 'Edit '.$invoice->invoice_number)

@section('content')
@include('invoices._form', [
    'document' => $document,
    'formAction' => route('invoices.update', $invoice),
    'formMethod' => 'PUT',
    'pageTitle' => 'Edit '.$invoice->invoice_number,
    'pageDescription' => 'Update the invoice details. After you save, the Word-style document refreshes.',
    'status' => $invoice->status,
    'dueDate' => optional($invoice->due_date)->format('Y-m-d'),
])
@endsection
