<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return view('admin.shifts.index', ['shifts' => Shift::orderBy('jam_mulai')->get()]);
    }

    public function create()
    {
        return view('admin.shifts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:50|unique:shifts,nama', 'jam_mulai' => 'required|date_format:H:i', 'jam_selesai' => 'required|date_format:H:i', 'toleransi_menit' => 'required|integer|min:0|max:120']);
        Shift::create($validated + ['is_aktif' => true]);

        return redirect()->route('admin.shifts.index')->with('success', 'Shift berhasil ditambahkan.');
    }
}
