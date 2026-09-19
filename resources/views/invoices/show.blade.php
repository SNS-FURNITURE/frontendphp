@extends('layouts.app')

@section('title', $invoice->invoice_number)
@section('content_class', 'content-wide')

@section('content')
@if ($canEdit)
    <div class="toolbar no-print" style="margin-bottom:1rem">
        <a class="btn" href="{{ route('invoices.edit', $invoice) }}">Edit details</a>
        <a class="btn ghost" href="{{ route('invoices.document', [$invoice, 'format' => 'pdf']) }}">Download PDF</a>
        <a class="btn ghost" href="{{ route('invoices.document', [$invoice, 'format' => 'html']) }}" target="_blank">Print</a>
        <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
        <span class="badge">{{ $invoice->status }}</span>
    </div>
@else
    <div class="toolbar no-print" style="margin-bottom:1rem">
        <a class="btn ghost" href="{{ route('invoices.document', [$invoice, 'format' => 'pdf']) }}">Download PDF</a>
        <a class="btn ghost" href="{{ route('invoices.document', [$invoice, 'format' => 'html']) }}" target="_blank">Print</a>
        <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
        <span class="badge">{{ $invoice->status }}</span>
    </div>
@endif

@include('invoices._editor', [
    'document' => $document,
    'readOnly' => true,
    'formAction' => null,
    'pageTitle' => $invoice->invoice_number,
    'pageDescription' => 'Saved invoice document (Word view).',
])
@endsection
