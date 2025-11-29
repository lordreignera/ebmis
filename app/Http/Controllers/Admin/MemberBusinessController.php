<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\BusinessAddress;
use Illuminate\Http\Request;

class MemberBusinessController extends Controller
{
    public function index(Member $member)
    {
        $businesses = $member->businesses()->with(['businessType', 'address'])->get();
        return view('admin.members.businesses.index', compact('member', 'businesses'));
    }

    public function create(Member $member)
    {
        $businessTypes = BusinessType::all();
        return view('admin.members.businesses.create', compact('member', 'businessTypes'));
    }

    public function store(Request $request, Member $member)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'reg_date' => 'nullable|string|max:30',
            'reg_no' => 'nullable|string|max:40',
            'tin' => 'nullable|string|max:40',
            'b_type' => 'required|exists:business_type,id',
            'pdt_1' => 'nullable|string|max:50',
            'pdt_2' => 'nullable|string|max:50',
            'pdt_3' => 'nullable|string|max:50',
            // Address fields
            'street' => 'nullable|string|max:50',
            'plot_no' => 'nullable|string|max:50',
            'house_no' => 'nullable|string|max:50',
            'cell' => 'nullable|string|max:50',
            'ward' => 'nullable|string|max:50',
            'division' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:15',
            'tel_no' => 'nullable|string|max:30',
            'mobile_no' => 'nullable|string|max:30',
            'fixed_line' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:50',
        ]);

        $business = $member->businesses()->create($validated);

        // Create business address
        $business->address()->create([
            'street' => $request->street,
            'plot_no' => $request->plot_no,
            'house_no' => $request->house_no,
            'cell' => $request->cell,
            'ward' => $request->ward,
            'division' => $request->division,
            'district' => $request->district,
            'country' => $request->country,
            'tel_no' => $request->tel_no,
            'mobile_no' => $request->mobile_no,
            'fixed_line' => $request->fixed_line,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Business profile added successfully');
    }

    public function edit(Member $member, Business $business)
    {
        $businessTypes = BusinessType::all();
        $business->load('address');
        return view('admin.members.businesses.edit', compact('member', 'business', 'businessTypes'));
    }

    public function update(Request $request, Member $member, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'reg_date' => 'nullable|string|max:30',
            'reg_no' => 'nullable|string|max:40',
            'tin' => 'nullable|string|max:40',
            'b_type' => 'required|exists:business_type,id',
            'pdt_1' => 'nullable|string|max:50',
            'pdt_2' => 'nullable|string|max:50',
            'pdt_3' => 'nullable|string|max:50',
        ]);

        $business->update($validated);

        // Update address
        $business->address()->updateOrCreate(
            ['business_id' => $business->id],
            [
                'street' => $request->street,
                'plot_no' => $request->plot_no,
                'house_no' => $request->house_no,
                'cell' => $request->cell,
                'ward' => $request->ward,
                'division' => $request->division,
                'district' => $request->district,
                'country' => $request->country,
                'tel_no' => $request->tel_no,
                'mobile_no' => $request->mobile_no,
                'fixed_line' => $request->fixed_line,
                'email' => $request->email,
            ]
        );

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Business profile updated successfully');
    }

    public function destroy(Member $member, Business $business)
    {
        $business->delete();
        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Business profile deleted successfully');
    }
}
