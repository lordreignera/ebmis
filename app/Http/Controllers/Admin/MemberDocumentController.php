<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberDocumentController extends Controller
{
    public function store(Request $request, Member $member)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:id_card,passport,bank_statement,payslip,utility_bill,business_license,tax_certificate,other',
            'document_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:20480', // 20MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('member-documents/' . $member->id, 'public');

        MemberDocument::create([
            'member_id' => $member->id,
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Document uploaded successfully');
    }

    public function download(Member $member, MemberDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return redirect()->back()->with('error', 'File not found');
        }

        return Storage::disk('public')->download($document->file_path, $document->document_name);
    }

    public function destroy(Member $member, MemberDocument $document)
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Document deleted successfully');
    }
}
