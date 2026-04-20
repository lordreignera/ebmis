<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanPolicyControl;
use Illuminate\Http\Request;

class LoanPolicyControlController extends Controller
{
    /**
     * Display all loan policy controls.
     */
    public function index()
    {
        $controls = LoanPolicyControl::orderByRaw("FIELD(`key`,
            'HF','MIN_DSCR','ABS_DSCR','STRESS',
            'APP_TH','ARA_TH','CON_TH','FRAUD_TH',
            'COL_MULT','MIN_IVI','MIN_VSS',
            'DVAR1','DVAR2','MPC',
            'MSR','AFR','IFR','RGF','FUC','FVC','LFR','MIC'
        )")->get();

        return view('admin.settings.loan-policy-controls', compact('controls'));
    }

    /**
     * Update one or more policy control values.
     */
    public function update(Request $request)
    {
        // Build dynamic validation rules
        $rules = [];
        foreach (LoanPolicyControl::all() as $control) {
            $paramKey = 'controls.' . $control->key;
            // integer fields like RGF (50,000) and FVC (3,000,000) need a much higher ceiling
            $max = $control->format === 'integer' ? 100_000_000 : 9999;
            $rules[$paramKey] = "required|numeric|min:0|max:{$max}";
        }

        $validated = $request->validate($rules);

        foreach ($validated['controls'] as $key => $rawValue) {
            $control = LoanPolicyControl::where('key', $key)->first();
            if (!$control) {
                continue;
            }

            // percent and multiplier: user enters 70 → store 0.70; score/integer: store as-is
            if (in_array($control->format, ['percent', 'multiplier'])) {
                $storeValue = (float) $rawValue / 100;
            } else {
                $storeValue = (float) $rawValue;
            }

            $control->update(['value' => $storeValue]);
        }

        // Invalidate cache so service picks up new values immediately
        LoanPolicyControl::clearCache();

        return redirect()->route('admin.settings.loan-policy-controls')
            ->with('success', 'Policy controls updated successfully.');
    }
}
