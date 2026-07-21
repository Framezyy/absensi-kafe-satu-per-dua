<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use Illuminate\Http\Request;

class IzinController extends Controller
{
    public function index()
    {
        $izin = Izin::with('karyawan')->orderBy('created_at', 'desc')->get();

        return view('admin.izin.index', compact('izin'));
    }

    public function approve($id)
    {
        $izin = Izin::findOrFail($id);
        if ($izin->status !== 'pending') {
            return back()->with('error', 'Izin sudah diproses.');
        }
        $izin->update(['status' => 'approved', 'diproses_oleh' => session('admin_user.id')]);

        return redirect()->route('admin.izin.index')->with('success', 'Izin disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $izin = Izin::findOrFail($id);
        if ($izin->status !== 'pending') {
            return back()->with('error', 'Izin sudah diproses.');
        }
        $izin->update(['status' => 'rejected', 'diproses_oleh' => session('admin_user.id')]);

        return redirect()->route('admin.izin.index')->with('error', 'Izin ditolak.');
    }
}
