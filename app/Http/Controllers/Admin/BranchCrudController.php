<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BranchCrudController extends Controller
{
    /**
     * Generate a unique branch code
     */
    private function generateBranchCode($name)
    {
        // Extract first 3-4 letters from branch name
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4));
        
        // Get the last branch code with this prefix
        $lastBranch = Branch::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();
        
        if ($lastBranch && preg_match('/(\d+)$/', $lastBranch->code, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }
        
        // Format: PREFIX001, PREFIX002, etc.
        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Display a listing of branches
     */
    public function index()
    {
        $branches = Branch::with('addedBy')->orderBy('name')->get();
        return view('admin.settings.branches', compact('branches'));
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string|max:255|unique:branches,name',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Auto-generate branch code
            $branchCode = $this->generateBranchCode($request->branch_name);
            
            $branch = Branch::create([
                'name' => $request->branch_name,
                'code' => $branchCode,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'country' => $request->country,
                'description' => $request->description,
                'is_active' => $request->status === 'active' ? 1 : 0,
                'added_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully!',
                'branch' => $branch
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch
     */
    public function show($id)
    {
        try {
            $branch = Branch::with('addedBy')->findOrFail($id);
            
            $branchData = $branch->toArray();
            
            // Format the added_by information
            if (isset($branchData['added_by'])) {
                $branchData['added_by_name'] = $branch->addedBy ? $branch->addedBy->name : 'System';
            }
            
            return response()->json([
                'success' => true,
                'branch' => $branchData
            ]);

        } catch (\Exception $e) {
            \Log::error('Branch show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Branch not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string|max:255|unique:branches,name,' . $id,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = Branch::findOrFail($id);
            
            $branch->update([
                'name' => $request->branch_name,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'country' => $request->country,
                'description' => $request->description,
                'is_active' => $request->status === 'active' ? 1 : 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully!',
                'branch' => $branch
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch
     */
    public function destroy($id)
    {
        try {
            $branch = Branch::findOrFail($id);
            
            // TODO: Check if branch has members, loans, etc. when needed
            
            $branch->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branch: ' . $e->getMessage()
            ], 500);
        }
    }
}
