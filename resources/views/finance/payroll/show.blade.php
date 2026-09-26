@extends('layouts.app')

@section('title', 'Payroll '.$run->period)

@section('content')
<div class="page-head">
    <div>
        <h1>Payroll {{ $run->period }}</h1>
        <p class="muted" style="margin:0.35rem 0 0">Status {{ $run->status }} · net {{ number_format((float) $run->total_net, 2) }} ETB</p>
    </div>
    <div class="toolbar">
        <a class="btn ghost" href="{{ route('finance.payroll.csv', $run->id) }}">CSV</a>
        <a class="btn ghost" href="{{ route('finance.payroll') }}">Back</a>
        @if ($canSendToManager)
            <form method="POST" action="{{ route('finance.payroll.status', $run->id) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="pending_manager">
                <button class="btn" type="submit">Send to Company Manager</button>
            </form>
        @endif
        @if ($canFinalize)
            <form method="POST" action="{{ route('finance.payroll.status', $run->id) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="processed">
                <button class="btn" type="submit">Approve payroll</button>
            </form>
            <form method="POST" action="{{ route('finance.payroll.status', $run->id) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <button class="btn ghost" type="submit">Reject</button>
            </form>
        @endif
        @if ($canMarkPaid)
            <form method="POST" action="{{ route('finance.payroll.status', $run->id) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="paid">
                <button class="btn" type="submit">Mark paid</button>
            </form>
        @endif
    </div>
</div>

<div class="card" style="overflow-x:auto">
    <table class="data">
        <thead>
        <tr>
            <th>Employee</th><th>Basic</th><th>Days</th><th>Gross</th><th>PAYE</th><th>Pension 7%</th><th>Net</th>
            @if ($canEdit)<th>Edit OT hrs</th>@endif
        </tr>
        </thead>
        <tbody>
        @foreach ($run->lines as $line)
            <tr>
                <td>{{ $line->employee_name }}<div class="muted">{{ $line->employee_number }}</div></td>
                <td>{{ number_format((float) $line->basic_salary, 2) }}</td>
                <td>{{ $line->days_worked }}</td>
                <td>{{ number_format((float) $line->gross, 2) }}</td>
                <td>{{ number_format((float) $line->paye, 2) }}</td>
                <td>{{ number_format((float) $line->employee_pension, 2) }}</td>
                <td>{{ number_format((float) $line->net, 2) }}</td>
                @if ($canEdit)
                    <td>
                        <form method="POST" action="{{ route('finance.payroll.line', [$run->id, $line->id]) }}" class="toolbar">
                            @csrf @method('PATCH')
                            <input type="number" step="0.01" name="overtime_hours" value="{{ $line->overtime_hours }}" style="width:5rem;margin:0">
                            <select name="overtime_multiplier" style="width:5rem;margin:0">
                                @foreach ([1.25, 1.5, 2.0] as $m)
                                    <option value="{{ $m }}" @selected((float)$line->overtime_multiplier === (float)$m)>{{ $m }}</option>
                                @endforeach
                            </select>
                            <button class="btn ghost" type="submit">Save</button>
                        </form>
                    </td>
                @endif
            </tr>
            @if ($line->formula_text)
                <tr><td colspan="{{ $canEdit ? 8 : 7 }}" class="muted" style="font-size:0.75rem">{{ $line->formula_text }}</td></tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
@endsection
