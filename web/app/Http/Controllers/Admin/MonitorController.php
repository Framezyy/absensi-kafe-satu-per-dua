<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceOverviewService;

class MonitorController extends Controller
{
    public function index(AttendanceOverviewService $overview)
    {
        $data = $overview->forDate(today());
        $summary = $overview->summary($data);

        return view('admin.monitor.index', compact('data', 'summary'));
    }
}
