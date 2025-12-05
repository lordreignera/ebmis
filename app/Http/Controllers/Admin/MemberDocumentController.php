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
        
        // Get file info BEFORE moving (temp file will be deleted after move)
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        
        // Store directly in public/uploads/member-documents/{member_id}/ 
        // No symlink needed - always accessible!
        $uploadPath = 'uploads/member-documents/' . $member->id;
        $publicPath = public_path($uploadPath);
        
        // Create directory if it doesn't exist
        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $extension;
        
        // Move file to public/uploads/
        $file->move($publicPath, $filename);
        
        // Store path relative to public folder
        $path = $uploadPath . '/' . $filename;

        MemberDocument::create([
            'member_id' => $member->id,
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $path,
            'file_type' => $mimeType,
            'file_size' => $fileSize,
            'description' => $validated['description'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Document uploaded successfully');
    }

    public function download(Member $member, MemberDocument $document)
    {
        // Check if file exists in public folder
        $filePath = public_path($document->file_path);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File not found');
        }

        return response()->download($filePath, $document->document_name);
    }

    public function destroy(Member $member, MemberDocument $document)
    {
        // Delete file from public folder
        $filePath = public_path($document->file_path);
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $document->delete();

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Document deleted successfully');
    }
}
