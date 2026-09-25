<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectedReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReportWebController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private DirectedReportService $reports,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canPostReport(), 403);

        $user = auth()->user();
        $query = Report::query()->with('author')->latestFirst('posted_at');
        if (! $user->canViewAllReports()) {
            $query->where(function ($q) use ($user) {
                $q->where('posted_by_user_id', $user->id);
                if (Schema::hasColumn('reports', 'sent_to_user_id')) {
                    $q->orWhere('sent_to_user_id', $user->id);
                }
            });
        }

        return view('reports.index', [
            'reports' => $query->limit(100)->get(),
            'users' => User::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
            'canPost' => $user->canPostReport(),
        ]);
    }

    public function library(): View
    {
        abort_unless(auth()->user()?->canViewAllReports(), 403);

        return view('reports.library', [
            'reports' => Report::query()->with('author')->latestFirst('posted_at')->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canPostReport(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2'],
            'recipient_user_id' => ['required', 'integer'],
            'body' => ['nullable', 'string'],
            'period' => ['nullable', 'string'],
            'highlights' => ['nullable', 'string'],
            'challenges' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
        ], [
            'title.required' => 'Report title is required',
            'recipient_user_id.required' => 'Please choose who this report is sent to',
        ]);

        $recipient = User::query()->where('id', $validated['recipient_user_id'])->where('is_active', true)->first();
        if (! $recipient) {
            return back()->withErrors(['recipient_user_id' => 'Recipient not found'])->withInput();
        }

        $parts = [];
        if (! empty($validated['period'])) {
            $parts[] = 'Period: '.$validated['period'];
        }
        if (! empty($validated['highlights'])) {
            $parts[] = "Highlights:\n".$validated['highlights'];
        }
        if (! empty($validated['challenges'])) {
            $parts[] = "Challenges:\n".$validated['challenges'];
        }
        if (! empty($validated['next_steps'])) {
            $parts[] = "Next steps:\n".$validated['next_steps'];
        }
        if (! empty($validated['body'])) {
            $parts[] = $validated['body'];
        }

        $created = $this->reports->insertDirectedReport(
            $validated['title'],
            implode("\n\n", $parts),
            auth()->user()->roles->first()?->name ?: 'general',
            auth()->user(),
            (int) $recipient->id,
            (string) $recipient->full_name,
        );

        $this->audit->log(auth()->user(), 'report', (int) $created['id'], 'SEND_REPORT', [
            'title' => $validated['title'],
            'recipient_user_id' => (int) $recipient->id,
        ], $request);

        return redirect()->route('reports.index')->with('status', 'Report sent');
    }
}
