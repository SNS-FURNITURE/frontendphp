<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Organization;
use Illuminate\View\View;

class OrgChartWebController extends Controller
{
    public function show(): View
    {
        abort_unless(auth()->user()?->canViewHr(), 403);

        return view('hr.org-chart', [
            'divisions' => Organization::divisions(),
        ]);
    }
}
