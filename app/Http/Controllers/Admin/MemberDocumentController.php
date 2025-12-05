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
        try {
            \Log::info('Document upload attempt', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'member_id' => $member->id,
                'has_file' => $request->hasFile('file'),
                'file_size' => $request->hasFile('file') ? $request->file('file')->getSize() : 0,
            ]);

            $validated = $request->validate([
                'document_type' => 'required|in:id_card,passport,bank_statement,payslip,utility_bill,business_license,tax_certificate,other',
                'document_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required|file|max:20480', // 20MB max
            ]);

            $file = $request->file('file');
            
            if (!$file) {
                \Log::error('No file in request', ['user' => auth()->user()->name]);
                return redirect()->back()->with('error', 'No file was uploaded. Please try again.');
            }
            
            // Get file info BEFORE moving (temp file will be deleted after move)
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            
            \Log::info('File details', [
                'mime' => $mimeType,
                'size' => $fileSize,
                'extension' => $extension,
            ]);
            
            // Store directly in public/uploads/member-documents/{member_id}/ 
            // No symlink needed - always accessible!
            $uploadPath = 'uploads/member-documents/' . $member->id;
            $publicPath = public_path($uploadPath);
            
            // Create directory if it doesn't exist
            if (!file_exists($publicPath)) {
                \Log::info('Creating directory', ['path' => $publicPath]);
                if (!mkdir($publicPath, 0755, true)) {
                    \Log::error('Failed to create directory', ['path' => $publicPath]);
                    return redirect()->back()->with('error', 'Failed to create upload directory. Please contact support.');
                }
            }
            
            // Generate unique filename
            $filename = uniqid() . '_' . time() . '.' . $extension;
            
            \Log::info('Moving file', [
                'from' => $file->getRealPath(),
                'to' => $publicPath . '/' . $filename,
            ]);
            
            // Move file to public/uploads/
            if (!$file->move($publicPath, $filename)) {
                \Log::error('Failed to move file', [
                    'destination' => $publicPath . '/' . $filename,
                ]);
                return redirect()->back()->with('error', 'Failed to save file. Please try again.');
            }
            
            // Store path relative to public folder
            $path = $uploadPath . '/' . $filename;

            $document = MemberDocument::create([
                'member_id' => $member->id,
                'document_type' => $validated['document_type'],
                'document_name' => $validated['document_name'],
                'file_path' => $path,
                'file_type' => $mimeType,
                'file_size' => $fileSize,
                'description' => $validated['description'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);

            \Log::info('Document uploaded successfully', [
                'document_id' => $document->id,
                'user' => auth()->user()->name,
                'member' => $member->id,
            ]);

            return redirect()->route('admin.members.show', $member)
                ->with('success', 'Document uploaded successfully');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error during upload', [
                'errors' => $e->errors(),
                'user' => auth()->user()->name,
            ]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Validation failed: ' . implode(', ', array_map(fn($err) => implode(', ', $err), $e->errors())));
                
        } catch (\Exception $e) {
            \Log::error('Document upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => auth()->user()->name,
                'member_id' => $member->id,
            ]);
            
            return redirect()->back()
                ->with('error', 'Upload failed: ' . $e->getMessage() . '. Please check the logs or contact support.');
        }
    }

    public function update(Request $request, Member $member, MemberDocument $document)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        
        // Get file info BEFORE moving (temp file will be deleted after move)
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        
        // Delete old file if exists
        if ($document->fileExists()) {
            $oldFilePath = public_path($document->file_path);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Store directly in public/uploads/member-documents/{member_id}/
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

        // Update document record
        $document->update([
            'file_path' => $path,
            'file_type' => $mimeType,
            'file_size' => $fileSize,
            'description' => $request->description ?? $document->description,
        ]);

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Document re-uploaded successfully');
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
