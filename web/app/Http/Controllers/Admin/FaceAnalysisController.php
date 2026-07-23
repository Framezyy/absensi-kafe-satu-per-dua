<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FaceAnalysisService;

class FaceAnalysisController extends Controller
{
    public function index(FaceAnalysisService $analysis)
    {
        $summary = $analysis->summary();
        $verifications = $analysis->verificationRows();

        return view('admin.face-analysis.index', compact('summary', 'verifications'));
    }
}
