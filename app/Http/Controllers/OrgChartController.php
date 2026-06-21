<?php

namespace App\Http\Controllers;

use App\Services\OrgChartService;
use Illuminate\View\View;

class OrgChartController extends Controller
{
    /**
     * Display the organisation chart.
     *
     * Open to any authenticated, active user (a transparency / self-service view).
     * Sensitive actions stay gated elsewhere; this only shows structure + heads.
     */
    public function index(OrgChartService $orgChart): View
    {
        return view('org-chart.index', $orgChart->tree());
    }
}
