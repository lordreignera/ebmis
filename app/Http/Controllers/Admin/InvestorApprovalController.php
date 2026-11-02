<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class InvestorApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'pending';
        $allowedStatuses = ['pending', 'approved', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $investors = Investor::query()
            ->with('countryDetails')
            ->where('portal_status', $status)
            ->where('soft_delete', 0)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = Investor::query()
            ->where('soft_delete', 0)
            ->whereNotNull('portal_status')
            ->selectRaw('portal_status, COUNT(*) as aggregate')
            ->groupBy('portal_status')
            ->pluck('aggregate', 'portal_status');

        return view('admin.investments.investor-approvals', compact('investors', 'counts', 'status'));
    }

    public function approve(Investor $investor): RedirectResponse
    {
        if (!$investor->password || !$investor->email || !$investor->image_path || !$investor->photo_path) {
            return back()->withErrors([
                'approval' => 'This registration is incomplete. A portal password and both KYC documents are required before approval.',
            ]);
        }

        if (!Storage::disk('local')->exists($investor->image_path)
            || !Storage::disk('local')->exists($investor->photo_path)) {
            return back()->withErrors([
                'approval' => 'One or more KYC files cannot be found. Ask the investor to submit the documents again before approval.',
            ]);
        }

        DB::transaction(function () use ($investor): void {
            $lockedInvestor = Investor::query()->lockForUpdate()->findOrFail($investor->id);
            $lockedInvestor->update([
                'portal_status' => 'approved',
                'status' => 1,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
        });

        return back()->with('success', $investor->full_name.' can now access the investor portal.');
    }

    public function document(Investor $investor, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['identity', 'photo'], true), 404);

        $path = $type === 'identity' ? $investor->image_path : $investor->photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Investor document not found.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = sprintf('investor-%d-%s.%s', $investor->id, $type, $extension ?: 'file');

        return Storage::disk('local')->download($path, $filename);
    }

    public function reject(Request $request, Investor $investor): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($investor, $validated): void {
            $lockedInvestor = Investor::query()->lockForUpdate()->findOrFail($investor->id);
            $lockedInvestor->update([
                'portal_status' => 'rejected',
                'status' => 0,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $validated['reason'],
            ]);
        });

        return back()->with('success', $investor->full_name.' was rejected for investor portal access.');
    }
}
