<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AdminController extends Controller
{
    /** Daftar semua user + pending approval. */
    public function users(Request $request)
    {
        $pending = User::where('is_approved', false)->latest()->get();
        $allUsers = User::latest()->paginate(20);

        return view('admin.users', compact('pending', 'allUsers'));
    }

    /** Approve akun user. */
    public function approve(Request $request, User $user)
    {
        $user->update(['is_approved' => true]);

        activity('admin')
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('Admin menyetujui akun user');

        return back()->with('status', "Akun {$user->name} berhasil disetujui.");
    }

    /** Reject / hapus akun yang belum disetujui. */
    public function reject(Request $request, User $user)
    {
        $name = $user->name;

        activity('admin')
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('Admin menolak akun user');

        $user->delete();

        return back()->with('status', "Akun {$name} ditolak dan dihapus.");
    }

    /** Update role / divisi user. */
    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,sales_marketing,engineering_capex',
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat mengubah role akun administrator Anda sendiri demi mencegah penguncian sistem (admin lockout).');
        }

        $user->syncRoles([$validated['role']]);

        activity('admin')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['new_role' => $validated['role']])
            ->log("Admin merubah role {$user->name} menjadi {$validated['role']}");

        return back()->with('status', "Role {$user->name} berhasil diperbarui.");
    }

    /** Audit log — tampilkan activity log dari spatie/activitylog. */
    public function auditLog(Request $request)
    {
        $logs = Activity::with('causer')
            ->latest()
            ->paginate(30);

        return view('admin.audit-log', compact('logs'));
    }
}
