<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;

class ShiftController extends Controller
{
    public function index()
    {
        return view('admin.shifts.index', ['shifts' => Shift::orderBy('jam_mulai')->get()]);
    }
}
