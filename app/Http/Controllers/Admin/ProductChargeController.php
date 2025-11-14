<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCharge;
use Illuminate\Http\Request;

class ProductChargeController extends Controller
{
    /**
     * Store a newly created product charge
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string|max:70',
            'type' => 'required|integer|in:1,2',
            'value' => 'required|numeric|min:0',
            'isactive' => 'integer|in:0,1',
        ]);

        try {
            $validated['added_by'] = auth()->id();
            $validated['isactive'] = $validated['isactive'] ?? 1;

            $charge = ProductCharge::create($validated);

            // Return JSON if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product charge added successfully.',
                    'charge' => $charge
                ]);
            }

            return redirect()->back()->with('success', 'Product charge added successfully.');

        } catch (\Exception $e) {
            // Return JSON error if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding product charge: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error adding product charge: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified product charge
     */
    public function update(Request $request, ProductCharge $productCharge)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:70',
            'type' => 'required|integer|in:1,2',
            'value' => 'required|numeric|min:0',
        ]);

        try {
            $productCharge->update($validated);

            // Return JSON if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product charge updated successfully.',
                    'charge' => $productCharge
                ]);
            }

            return redirect()->back()->with('success', 'Product charge updated successfully.');

        } catch (\Exception $e) {
            // Return JSON error if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating product charge: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating product charge: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product charge
     */
    public function destroy(Request $request, ProductCharge $productCharge)
    {
        try {
            $productCharge->delete();

            // Return JSON if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product charge deleted successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'Product charge deleted successfully.');

        } catch (\Exception $e) {
            // Return JSON error if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting product charge: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->with('error', 'Error deleting product charge: ' . $e->getMessage());
        }
    }
}
