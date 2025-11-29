<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Http\Request;

class MemberAssetController extends Controller
{
    public function store(Request $request, Member $member)
    {
        $validated = $request->validate([
            'asset_type' => 'required|exists:asset_types,id',
            'business_id' => 'nullable|exists:business,id',
            'quantity' => 'required|integer|min:1',
            'value' => 'required|integer|min:0',
        ]);

        $validated['member_id'] = $member->id;
        $validated['asset_id'] = $validated['asset_type']; // Legacy column

        Asset::create($validated);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Asset added successfully');
    }

    public function update(Request $request, Member $member, Asset $asset)
    {
        $validated = $request->validate([
            'asset_type' => 'required|exists:asset_types,id',
            'business_id' => 'nullable|exists:business,id',
            'quantity' => 'required|integer|min:1',
            'value' => 'required|integer|min:0',
        ]);

        $validated['asset_id'] = $validated['asset_type'];
        $asset->update($validated);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Asset updated successfully');
    }

    public function destroy(Member $member, Asset $asset)
    {
        $asset->delete();
        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Asset deleted successfully');
    }
}
