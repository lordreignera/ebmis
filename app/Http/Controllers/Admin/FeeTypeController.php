<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use Illuminate\Http\Request;

class FeeTypeController extends Controller
{
    /**
     * Store a newly created fee type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|integer|exists:system_accounts,id',
            'isactive' => 'nullable|boolean',
            'required_disbursement' => 'nullable|boolean',
        ]);

        try {
            $validated['added_by'] = auth()->id();
            $validated['isactive'] = $request->has('isactive') ? 1 : 0;
            $validated['required_disbursement'] = $request->has('required_disbursement') ? 1 : 0;

            $feeType = FeeType::create($validated);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee type created successfully.',
                    'feeType' => $feeType
                ]);
            }

            return redirect()->back()->with('success', 'Fee type created successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating fee type: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating fee type: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified fee type
     */
    public function show(Request $request)
    {
        try {
            $id = $request->query('id');
            
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee type ID is required'
                ], 400);
            }
            
            $feeType = FeeType::with('systemAccount')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'feeType' => $feeType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified fee type
     */
    public function update(Request $request, $fee_type)
    {
        $id = $fee_type;
        $feeType = FeeType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|integer|exists:system_accounts,id',
            'isactive' => 'nullable|boolean',
            'required_disbursement' => 'nullable|boolean',
        ]);

        try {
            $validated['isactive'] = $request->has('isactive') ? 1 : 0;
            $validated['required_disbursement'] = $request->has('required_disbursement') ? 1 : 0;
            
            $feeType->update($validated);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee type updated successfully.',
                    'feeType' => $feeType
                ]);
            }

            return redirect()->back()->with('success', 'Fee type updated successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating fee type: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating fee type: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified fee type
     */
    public function destroy(Request $request, $fee_type)
    {
        $id = $fee_type;
        $feeType = FeeType::findOrFail($id);

        try {
            // Check if fee type has associated fees
            if ($feeType->fees()->count() > 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete fee type with associated fees.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Cannot delete fee type with associated fees.');
            }

            $feeType->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee type deleted successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'Fee type deleted successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting fee type: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->with('error', 'Error deleting fee type: ' . $e->getMessage());
        }
    }
}
