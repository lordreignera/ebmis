<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Liability;
use App\Models\LiabilityType;
use Illuminate\Http\Request;

class MemberLiabilityController extends Controller
{
    public function store(Request $request, Member $member)
    {
        $validated = $request->validate([
            'liability_type' => 'required|exists:liability_types,id',
            'business_id' => 'nullable|exists:business,id',
            'value' => 'required|integer|min:0',
        ]);

        $validated['member_id'] = $member->id;
        $validated['liability_id'] = $validated['liability_type']; // Legacy column

        Liability::create($validated);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Liability added successfully');
    }

    public function update(Request $request, Member $member, Liability $liability)
    {
        $validated = $request->validate([
            'liability_type' => 'required|exists:liability_types,id',
            'business_id' => 'nullable|exists:business,id',
            'value' => 'required|integer|min:0',
        ]);

        $validated['liability_id'] = $validated['liability_type'];
        $liability->update($validated);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Liability updated successfully');
    }

    public function destroy(Member $member, Liability $liability)
    {
        $liability->delete();
        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Liability deleted successfully');
    }
}
