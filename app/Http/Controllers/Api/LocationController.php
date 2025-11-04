<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UgandaDistrict;
use App\Models\UgandaSubcounty;
use App\Models\UgandaParish;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get all districts
     */
    public function getDistricts()
    {
        $districts = UgandaDistrict::orderBy('name')->get(['id', 'name', 'region']);
        
        return response()->json([
            'success' => true,
            'data' => $districts
        ]);
    }

    /**
     * Get subcounties by district
     */
    public function getSubcounties($districtId)
    {
        $subcounties = UgandaSubcounty::where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
        
        return response()->json([
            'success' => true,
            'data' => $subcounties
        ]);
    }

    /**
     * Get parishes by subcounty
     */
    public function getParishes($subcountyId)
    {
        $parishes = UgandaParish::where('subcounty_id', $subcountyId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json([
            'success' => true,
            'data' => $parishes
        ]);
    }

    /**
     * Get villages by parish
     */
    public function getVillages($parishId)
    {
        $villages = \App\Models\UgandaVillage::where('parish_id', $parishId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json([
            'success' => true,
            'data' => $villages
        ]);
    }

    /**
     * Search locations
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $districts = UgandaDistrict::where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'region']);
        
        $subcounties = UgandaSubcounty::where('name', 'like', "%{$query}%")
            ->with('district:id,name')
            ->limit(10)
            ->get(['id', 'district_id', 'name', 'type']);
        
        $parishes = UgandaParish::where('name', 'like', "%{$query}%")
            ->with('subcounty.district')
            ->limit(10)
            ->get(['id', 'subcounty_id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'districts' => $districts,
                'subcounties' => $subcounties,
                'parishes' => $parishes
            ]
        ]);
    }
}
