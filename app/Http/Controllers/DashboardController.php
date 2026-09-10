<?php

namespace App\Http\Controllers;

use App\Models\SimulasiAki;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $totalSimulasi = SimulasiAki::count();
            $layak = SimulasiAki::where('layak', true)->count();
            $tidakLayak = SimulasiAki::where('layak', false)->count();
            $pendingUsers = \App\Models\User::where('is_approved', false)->count();
            $pendingReview = SimulasiAki::where('status', SimulasiAki::STATUS_MENUNGGU_REVIEW)->count();
            $latestSimulasi = SimulasiAki::latest()->take(10)->get();

            return view('dashboard', compact('user', 'totalSimulasi', 'layak', 'tidakLayak', 'pendingUsers', 'pendingReview', 'latestSimulasi'));
        }

        $simulasis = SimulasiAki::forUser($user)->latest()->take(10)->get();
        $totalSimulasi = SimulasiAki::forUser($user)->count();
        $layak = SimulasiAki::forUser($user)->where('layak', true)->count();
        $tidakLayak = SimulasiAki::forUser($user)->where('layak', false)->count();
        $revisi = SimulasiAki::forUser($user)->where('status', SimulasiAki::STATUS_REVISI)->count();
        $pendingEd = SimulasiAki::where('status', SimulasiAki::STATUS_MENUNGGU_PIHAK_A)->count();

        return view('dashboard', compact('user', 'simulasis', 'totalSimulasi', 'layak', 'tidakLayak', 'revisi', 'pendingEd'));
    }
}
