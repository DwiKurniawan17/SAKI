<?php

namespace App\Http\Controllers;

use App\Models\ParameterRdm;
use Illuminate\Http\Request;

class RdmController extends Controller
{
    public function index(Request $request)
    {
        $rdm = ParameterRdm::getActive();

        return view('admin.rdm', compact('rdm'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rdm_otc_pct' => 'required|numeric|min:0|max:100',
            'rdm_mrc_pct' => 'required|numeric|min:0|max:100',
        ]);

        $rdm = ParameterRdm::getActive();
        $rdm->update([
            'rdm_otc_pct' => $validated['rdm_otc_pct'],
            'rdm_mrc_pct' => $validated['rdm_mrc_pct'],
            'updated_by'  => $request->user()->id,
        ]);

        activity('admin')
            ->causedBy($request->user())
            ->performedOn($rdm)
            ->withProperties($validated)
            ->log('Admin mengubah parameter RDM');

        return back()->with('status', 'Parameter RDM berhasil diperbarui! Nilai ini akan digunakan untuk simulasi baru.');
    }
}
