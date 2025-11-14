<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AgencyController extends Controller
{
    /**
     * Generate a unique agency code
     */
    private function generateAgencyCode($name)
    {
        // Extract first 3-4 letters from agency name
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4));
        
        // Get the last agency code with this prefix
        $lastAgency = Agency::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();
        
        if ($lastAgency && preg_match('/(\d+)$/', $lastAgency->code, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }
        
        // Format: PREFIX001, PREFIX002, etc.
        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Display a listing of agencies
     */
    public function index()
    {
        $agencies = Agency::with('addedBy')->orderBy('name')->get();
        return view('admin.settings.agencies', compact('agencies'));
    }

    /**
     * Store a newly created agency
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agency_name' => 'required|string|max:255|unique:agency,name',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location' => 'nullable|string',
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
            // Auto-generate agency code
            $agencyCode = $this->generateAgencyCode($request->agency_name);
            
            $agency = Agency::create([
                'name' => $request->agency_name,
                'code' => $agencyCode,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'location' => $request->location,
                'description' => $request->description,
                'isactive' => $request->status === 'active' ? 1 : 0,
                'added_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agency created successfully!',
                'agency' => $agency
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create agency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified agency
     */
    public function show($id)
    {
        try {
            $agency = Agency::with('addedBy')->findOrFail($id);
            
            $agencyData = $agency->toArray();
            
            // Add branch count as 0 since the relationship doesn't exist yet
            $agencyData['branches'] = ['length' => 0];
            
            // Format the added_by information
            if (isset($agencyData['added_by'])) {
                $agencyData['added_by_name'] = $agency->addedBy ? $agency->addedBy->name : 'System';
            }
            
            return response()->json([
                'success' => true,
                'agency' => $agencyData
            ]);

        } catch (\Exception $e) {
            \Log::error('Agency show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Agency not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified agency
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'agency_name' => 'required|string|max:255|unique:agency,name,' . $id,
            'agency_code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location' => 'nullable|string',
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
            $agency = Agency::findOrFail($id);
            
            $agency->update([
                'name' => $request->agency_name,
                'code' => $request->agency_code,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'location' => $request->location,
                'description' => $request->description,
                'isactive' => $request->status === 'active' ? 1 : 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agency updated successfully!',
                'agency' => $agency
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update agency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified agency
     */
    public function destroy($id)
    {
        try {
            $agency = Agency::findOrFail($id);
            
            // TODO: Check if agency has branches when the relationship is implemented
            // For now, allow deletion since branches table doesn't have agency_id column

            $agency->delete();

            return response()->json([
                'success' => true,
                'message' => 'Agency deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete agency: ' . $e->getMessage()
            ], 500);
        }
    }
}
