@extends('layouts.app')

@section('title', 'Edit '.$invoice->invoice_number)
@section('content_class', 'content-wide')

@section('content')
@include('invoices._editor', [
    'document' => $document,
    'readOnly' => false,
    'formAction' => route('invoices.update', $invoice),
    'formMethod' => 'PUT',
    'pageTitle' => 'Edit '.$invoice->invoice_number,
    'pageDescription' => 'Edit like Word, then save draft or issue.',
    'status' => $invoice->status,
])
@endsection
