<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        return view('pages.manual', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeSection' => $request->query('section', 'calendar-sync'),
        ]);
    }
}
