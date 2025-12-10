<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberDocumentController extends Controller
{
    public function store(Request $request, Member $member)
    {
        try {
            $file = $request->file('file');
            
            \Log::info('Document upload attempt', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'member_id' => $member->id,
                'has_file' => $request->hasFile('file'),
                'file_size' => $file ? $file->getSize() : 0,
                'file_error' => $file ? $file->getError() : null,
                'file_valid' => $file ? $file->isValid() : false,
                'original_name' => $file ? $file->getClientOriginalName() : null,
                'temp_path' => $file ? $file->getRealPath() : null,
            ]);
            
            // Check for upload errors BEFORE validation
            if ($file && $file->getError() !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                ];
                
                $errorCode = $file->getError();
                $errorMsg = $errorMessages[$errorCode] ?? 'Unknown upload error';
                
                \Log::error('Upload error before validation', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMsg,
                    'user' => auth()->user()->name,
                ]);
                
                return redirect()->back()->with('error', 'Upload failed: ' . $errorMsg . '. Please try a smaller file or contact support.');
            }

            $validated = $request->validate([
                'document_type' => 'required|in:id_card,passport,bank_statement,payslip,utility_bill,business_license,tax_certificate,other',
                'document_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required|file|max:51200', // 50MB max - increased limit
            ]);

            // File already retrieved earlier, no need to get it again
            
            // Get file info BEFORE moving (temp file will be deleted after move)
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            
            // Log PDF info but don't reject - accept all PDF formats
            if (strtolower($extension) === 'pdf' || strpos($mimeType, 'pdf') !== false) {
                $fileContent = file_get_contents($file->getRealPath());
                
                // Just log PDF header info for debugging, don't reject
                $header = substr($fileContent, 0, 10);
                \Log::info('PDF file info', [
                    'user' => auth()->user()->name,
                    'has_pdf_header' => (substr($fileContent, 0, 5) === '%PDF-'),
                    'first_bytes' => bin2hex($header),
                    'size' => $fileSize,
                    'is_encrypted' => (strpos($fileContent, '/Encrypt') !== false),
                ]);
            }
            
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
            
            // Upload file using FileStorageService (auto-uploads to DigitalOcean Spaces in production)
            try {
                $path = FileStorageService::storeFile($file, 'member-documents/' . $member->id);
            } catch (\Exception $e) {
                \Log::error('Failed to upload file', [
                    'error' => $e->getMessage(),
                ]);
                return redirect()->back()->with('error', 'Failed to save file. Please try again.');
            }

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
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:51200', // 50MB max - accept any file type
                'description' => 'nullable|string',
            ]);

            $file = $request->file('file');
            
            // Get file info BEFORE moving (temp file will be deleted after move)
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            
            // Log PDF info for debugging but don't reject
            if (strtolower($extension) === 'pdf' || strpos($mimeType, 'pdf') !== false) {
                \Log::info('PDF re-upload', [
                    'user' => auth()->user()->name,
                    'document_id' => $document->id,
                    'size' => $fileSize,
                    'mime' => $mimeType,
                ]);
            }
        
        // Delete old file if exists
        if ($document->fileExists()) {
            $oldFilePath = public_path($document->file_path);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Store directly in public/uploads/member-documents/{member_id}/
        $uploadPath = 'uploads/member-documents/' . $member->id;
        // Upload file using FileStorageService (auto-uploads to DigitalOcean Spaces in production)
        $path = FileStorageService::storeFile($file, 'member-documents/' . $member->id);

            // Update document record
            $document->update([
                'file_path' => $path,
                'file_type' => $mimeType,
                'file_size' => $fileSize,
                'description' => $request->description ?? $document->description,
            ]);

            \Log::info('Document re-uploaded successfully', [
                'document_id' => $document->id,
                'user' => auth()->user()->name,
            ]);

            return redirect()->route('admin.members.show', $member)
                ->with('success', 'Document re-uploaded successfully');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error during re-upload', [
                'errors' => $e->errors(),
                'user' => auth()->user()->name,
            ]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', 'Validation failed: ' . implode(', ', array_map(fn($err) => implode(', ', $err), $e->errors())));
                
        } catch (\Exception $e) {
            \Log::error('Document re-upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => auth()->user()->name,
                'document_id' => $document->id,
            ]);
            
            return redirect()->back()
                ->with('error', 'Re-upload failed: ' . $e->getMessage());
        }
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
