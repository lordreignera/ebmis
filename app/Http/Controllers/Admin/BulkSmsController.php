<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\BulkSms;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkSmsController extends Controller
{
    public function index(Request $request)
    {
        $query = BulkSms::with(['sentBy'])
                        ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        // Date range filtering
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Status filtering
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $bulkSms = $query->paginate(20);

        $stats = [
            'total_campaigns' => BulkSms::count(),
            'total_sent' => BulkSms::sum('recipients_count'),
            'successful_sent' => BulkSms::sum('successful_count'),
            'failed_sent' => BulkSms::sum('failed_count'),
            'pending_campaigns' => BulkSms::where('status', 'pending')->count(),
            'today_campaigns' => BulkSms::whereDate('created_at', today())->count(),
        ];

        return view('admin.bulk-sms.index', compact('bulkSms', 'stats'));
    }

    public function create()
    {
        $members = Member::where('status', 'approved')
                        ->whereNotNull('contact')
                        ->select('id', 'fname', 'lname', 'contact', 'code')
                        ->get();

        $memberGroups = [
            'all' => 'All Members',
            'new' => 'New Members (Last 30 days)',
            'active_borrowers' => 'Active Borrowers',
            'overdue' => 'Members with Overdue Loans',
            'individual' => 'Individual Members',
            'group' => 'Group Members',
        ];

        return view('admin.bulk-sms.create', compact('members', 'memberGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'recipient_type' => 'required|in:all,group,individual',
            'recipient_group' => 'required_if:recipient_type,group',
            'recipients' => 'required_if:recipient_type,individual|array',
            'recipients.*' => 'exists:members,id',
            'schedule_type' => 'required|in:now,later',
            'scheduled_at' => 'required_if:schedule_type,later|date|after:now',
        ]);

        // Get recipients based on selection
        $recipients = $this->getRecipients($request);

        if ($recipients->isEmpty()) {
            return back()->withErrors(['recipients' => 'No valid recipients found.']);
        }

        $bulkSms = BulkSms::create([
            'title' => $request->title,
            'message' => $request->message,
            'recipient_type' => $request->recipient_type,
            'recipient_group' => $request->recipient_group,
            'recipients_count' => $recipients->count(),
            'status' => $request->schedule_type === 'now' ? 'sending' : 'scheduled',
            'scheduled_at' => $request->schedule_type === 'later' ? $request->scheduled_at : now(),
            'sent_by' => auth()->id(),
        ]);

        // Store recipient details
        foreach ($recipients as $member) {
            SmsLog::create([
                'sms_id' => $bulkSms->id,
                'member_id' => $member->id,
                'phone' => $member->contact,
                'message' => $request->message,
                'status' => 'pending',
            ]);
        }

        if ($request->schedule_type === 'now') {
            // Process immediately (in real implementation, this would go to a queue)
            $this->processBulkSms($bulkSms);
        }

        return redirect()->route('admin.bulk-sms.index')
                        ->with('success', 'Bulk SMS campaign created successfully');
    }

    public function show($id)
    {
        $bulkSms = BulkSms::with(['sentBy', 'smsLogs.member'])->findOrFail($id);
        
        $stats = [
            'total_recipients' => $bulkSms->recipients_count,
            'successful' => $bulkSms->successful_count,
            'failed' => $bulkSms->failed_count,
            'pending' => $bulkSms->smsLogs->where('status', 'pending')->count(),
        ];

        return view('admin.bulk-sms.show', compact('bulkSms', 'stats'));
    }

    public function destroy($id)
    {
        $bulkSms = BulkSms::findOrFail($id);
        
        if ($bulkSms->status === 'sending') {
            return back()->withErrors(['error' => 'Cannot delete campaign that is currently being sent.']);
        }

        // Delete related SMS logs
        $bulkSms->smsLogs()->delete();
        $bulkSms->delete();

        return redirect()->route('admin.bulk-sms.index')
                        ->with('success', 'Bulk SMS campaign deleted successfully');
    }

    private function getRecipients(Request $request)
    {
        $query = Member::where('status', 'approved')
                      ->whereNotNull('contact');

        if ($request->recipient_type === 'individual') {
            return $query->whereIn('id', $request->recipients)->get();
        }

        if ($request->recipient_type === 'group') {
            switch ($request->recipient_group) {
                case 'all':
                    break; // No additional filter
                case 'new':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
                case 'active_borrowers':
                    $query->whereHas('loans', function ($q) {
                        $q->where('status', 'disbursed')
                          ->where('outstanding_amount', '>', 0);
                    });
                    break;
                case 'overdue':
                    $query->whereHas('loans', function ($q) {
                        $q->where('status', 'disbursed')
                          ->where('due_date', '<', now())
                          ->where('outstanding_amount', '>', 0);
                    });
                    break;
                case 'individual':
                    $query->where('member_type', 'individual');
                    break;
                case 'group':
                    $query->where('member_type', 'group');
                    break;
            }
        }

        return $query->get();
    }

    private function processBulkSms(BulkSms $bulkSms)
    {
        // In a real implementation, this would integrate with an SMS gateway
        // For now, we'll simulate the process
        
        $smsLogs = $bulkSms->smsLogs()->where('status', 'pending')->get();
        $successCount = 0;
        $failCount = 0;

        foreach ($smsLogs as $smsLog) {
            // Simulate SMS sending (90% success rate)
            $success = rand(1, 100) <= 90;
            
            if ($success) {
                $smsLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $successCount++;
            } else {
                $smsLog->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to send SMS',
                ]);
                $failCount++;
            }
        }

        $bulkSms->update([
            'status' => 'completed',
            'successful_count' => $successCount,
            'failed_count' => $failCount,
            'completed_at' => now(),
        ]);
    }
}