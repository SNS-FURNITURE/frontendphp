<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditWebController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $query = AuditLog::query()
            ->with('user:id,full_name,email')
            ->latestFirst()
            ->limit(100);

        $entityType = $request->string('entity_type')->toString();
        if ($entityType !== '') {
            $query->where('entity_type', $entityType);
        }

        $entries = $query->get();
        $types = AuditLog::query()->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type');

        return view('admin.audit-log', compact('entries', 'types', 'entityType'));
    }
}
